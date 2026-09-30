<?php

namespace Tests;

use Illuminate\Support\Carbon;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Statamic\Facades\Entry;
use Statamic\Facades\Term;
use Statamic\Facades\User;
use Statamic\SeoPro\Reporting\Report;
use Statamic\SeoPro\Widgets\SeoProWidget;

class SeoProWidgetTest extends TestCase
{
    private const REPORT_URL = 'http://cool-runnings.com/cp/seo-pro/reports/1';

    private const NBSP = "\u{00A0}";

    protected function setUp(): void
    {
        parent::setUp();

        Entry::all()->each->delete();
        Term::all()->each->delete();

        if ($this->files->exists($path = storage_path('statamic/seopro/reports'))) {
            $this->files->deleteDirectory($path);
        }

        config(['statamic.system.display_timezone' => 'Europe/Zurich']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function widget(array $config = []): SeoProWidget
    {
        $widget = new SeoProWidget;
        $widget->setConfig($config);

        return $widget;
    }

    private function props(array $config = []): array
    {
        return $this->widget($config)->component()->toArray()['props'];
    }

    private function invoke(string $method, array $config, ...$args)
    {
        $widget = $this->widget($config);

        return (new ReflectionMethod($widget, $method))->invoke($widget, ...$args);
    }

    private function actingAsSuper(): self
    {
        return $this->actingAs(User::make()->makeSuper()->save());
    }

    // Component

    #[Test]
    public function it_renders_nothing_without_permission_to_view_reports()
    {
        $this->actingAs(User::make()->save());

        $this->assertNull($this->widget()->component());
    }

    #[Test]
    public function it_passes_no_report_when_none_was_generated()
    {
        $this->actingAsSuper();

        $props = $this->props();

        $this->assertNull($props['report']);
        $this->assertSame('SEO Pro', $props['title']);
        $this->assertTrue($props['showRules']);
    }

    #[Test]
    public function it_passes_the_configured_options()
    {
        $this->actingAsSuper();

        $props = $this->props(['title' => 'SEO Check', 'show_rules' => false]);

        $this->assertSame('SEO Check', $props['title']);
        $this->assertFalse($props['showRules']);
    }

    #[Test]
    public function it_passes_the_score_and_the_open_rules_of_the_latest_report()
    {
        $this->actingAsSuper();

        collect(range(1, 5))->each(fn ($i) => Entry::make()
            ->collection('articles')
            ->blueprint('article')
            ->slug('test_entry_'.$i)
            ->set('title', 'Test Entry '.$i)
            ->save());

        Report::create()->save()->generate();

        $report = $this->props()['report'];

        $this->assertSame(self::REPORT_URL, $report['url']);
        $this->assertSame((int) round(Report::find(1)->score()), $report['score']);
        $this->assertSame('7 rules checked', $report['rulesChecked']);

        $rule = $report['openRules']->firstWhere('handle', 'NoUnderscoresInUrl');
        $this->assertSame('fail', $rule['status']);
        $this->assertSame('5 pages', $rule['badge']);
        $this->assertSame(self::REPORT_URL.'?rule=NoUnderscoresInUrl', $rule['url']);

        $this->assertNotContains('pass', $report['openRules']->pluck('status'));
        $passed = 7 - $report['openRules']->count();
        $this->assertSame($passed === 1 ? '1 rule passed' : "{$passed} rules passed", $report['passed']);
    }

    #[Test]
    public function it_treats_a_legacy_report_without_a_score_like_no_report()
    {
        $this->actingAsSuper();

        $this->files->makeDirectory(storage_path('statamic/seopro/reports/1'), 0755, true);
        $this->files->put(storage_path('statamic/seopro/reports/1/report.yaml'), <<<'YAML'
date: 1700000000
status: fail
results:
  SiteName: true
YAML);

        $this->assertNull($this->props()['report']);
    }

    // Rules

    private function rules(array $rows, array $raw)
    {
        $report = Mockery::mock(Report::class);
        $report->shouldReceive('results')->andReturn($raw);

        return $this->invoke('rules', [], $report, $rows, self::REPORT_URL);
    }

    private function row(string $handle, string $status, bool $filterable = true, string $description = ''): array
    {
        return ['handle' => $handle, 'is_filterable' => $filterable, 'description' => $description, 'status' => $status, 'comment' => ''];
    }

    #[Test]
    public function site_rules_have_no_page_count_and_no_link()
    {
        $rule = $this->rules([$this->row('SiteName', 'fail', filterable: false)], ['SiteName' => false])->first();

        $this->assertSame('Site name', $rule['label']);
        $this->assertSame('Site', $rule['badge']);
        $this->assertNull($rule['url']);
    }

    #[Test]
    public function page_rules_count_pages_and_link_to_the_filtered_report()
    {
        $rule = $this->rules([$this->row('NoUnderscoresInUrl', 'fail')], ['NoUnderscoresInUrl' => 3])->first();

        $this->assertSame('URL without underscores', $rule['label']);
        $this->assertSame('3 pages', $rule['badge']);
        $this->assertSame(self::REPORT_URL.'?rule=NoUnderscoresInUrl', $rule['url']);
    }

    #[Test]
    public function one_page_is_singular()
    {
        $rule = $this->rules([$this->row('ThreeSegmentUrls', 'warning')], ['ThreeSegmentUrls' => 1])->first();

        $this->assertSame('1 page', $rule['badge']);
    }

    #[Test]
    public function length_rules_count_failures_and_warnings_together()
    {
        // Same number as the page list the report shows when filtered by the rule.
        $rule = $this->rules(
            [$this->row('IdealTitleLength', 'fail')],
            ['IdealTitleLength' => ['failures' => 1, 'warnings' => 5]],
        )->first();

        $this->assertSame('6 pages', $rule['badge']);
    }

    #[Test]
    public function rules_keep_the_status_and_order_of_the_report()
    {
        $rules = $this->rules(
            [$this->row('UniqueTitleTag', 'pass'), $this->row('IdealTitleLength', 'fail'), $this->row('ThreeSegmentUrls', 'warning')],
            ['UniqueTitleTag' => 0, 'IdealTitleLength' => ['failures' => 1, 'warnings' => 0], 'ThreeSegmentUrls' => 2],
        );

        $this->assertSame(['UniqueTitleTag', 'IdealTitleLength', 'ThreeSegmentUrls'], $rules->pluck('handle')->all());
        $this->assertSame(['pass', 'fail', 'warning'], $rules->pluck('status')->all());
    }

    #[Test]
    public function unknown_rules_fall_back_to_the_description_without_markup()
    {
        $rule = $this->rules(
            [$this->row('FutureRule', 'fail', description: '<strong>A future rule.</strong>')],
            ['FutureRule' => 2],
        )->first();

        $this->assertSame('A future rule.', $rule['label']);
    }

    // Freshness (times in UTC, displayed in Europe/Zurich)

    private function freshness(string $reportUtc, string $nowUtc, array $config = []): array
    {
        config(['statamic.system.track_last_update' => false]);
        Carbon::setTestNow(Carbon::parse($nowUtc, 'UTC'));

        $report = Mockery::mock(Report::class);
        $report->shouldReceive('date')->andReturnUsing(fn () => Carbon::parse($reportUtc, 'UTC'));

        return $this->invoke('freshness', $config, $report);
    }

    #[Test]
    public function freshness_shows_the_time_in_the_display_timezone()
    {
        // 07:41 UTC is 09:41 in Zurich (summer time).
        $this->assertSame(
            ['stale' => false, 'text' => 'Generated today at 9:41 AM.'],
            $this->freshness('2026-09-30 07:41', '2026-09-30 12:00'),
        );
    }

    #[Test]
    public function freshness_uses_the_display_timezone_for_the_day_boundary()
    {
        // 22:30 UTC the day before is 00:30 in Zurich – still today.
        $this->assertSame('Generated today at 12:30 AM.', $this->freshness('2026-09-29 22:30', '2026-09-30 07:00')['text']);
        $this->assertSame('Generated yesterday at 11:50 PM.', $this->freshness('2026-09-29 21:50', '2026-09-30 07:00')['text']);
    }

    #[Test]
    public function freshness_counts_days()
    {
        $this->assertSame('Generated 5 days ago.', $this->freshness('2026-09-25 10:00', '2026-09-30 10:00')['text']);
    }

    #[Test]
    public function freshness_turns_stale_after_the_threshold()
    {
        $this->assertFalse($this->freshness('2026-08-31 10:00', '2026-09-30 10:00')['stale'], '30 days');
        $this->assertTrue($this->freshness('2026-08-30 10:00', '2026-09-30 10:00')['stale'], '31 days');
        $this->assertTrue($this->freshness('2026-09-22 10:00', '2026-09-30 10:00', ['stale_after_days' => 7])['stale']);
    }

    #[Test]
    public function stale_freshness_rounds_to_the_largest_unit()
    {
        $this->assertSame(
            ['stale' => true, 'text' => 'Generated 3'.self::NBSP.'months ago. A lot has probably changed since.'],
            $this->freshness('2026-06-30 10:00', '2026-09-30 10:00'),
        );
    }

    #[Test]
    public function a_report_from_the_future_counts_as_today()
    {
        $result = $this->freshness('2026-10-02 10:00', '2026-09-30 10:00');

        $this->assertFalse($result['stale']);
        $this->assertStringStartsWith('Generated today at ', $result['text']);
    }

    #[Test]
    public function freshness_adds_the_change_count_when_edits_are_tracked()
    {
        Carbon::setTestNow(Carbon::parse('2026-09-30 12:00', 'UTC'));
        config(['statamic.system.track_last_update' => true]);

        $report = Mockery::mock(Report::class);
        $report->shouldReceive('date')->andReturnUsing(fn () => Carbon::parse('2026-09-30 07:41', 'UTC'));

        $this->assertSame(
            'Generated today at 9:41 AM. Nothing has been edited since.',
            $this->invoke('freshness', [], $report)['text'],
        );
    }

    // Changes since the report

    private const REPORT_AT = 1_700_000_000;

    private function reportAt(): Report
    {
        $report = Mockery::mock(Report::class);
        $report->shouldReceive('date')->andReturn(Carbon::createFromTimestamp(self::REPORT_AT));

        return $report;
    }

    private function entry(string $slug, ?int $updatedAt, array $data = []): void
    {
        $entry = Entry::make()->collection('articles')->blueprint('article')->slug($slug)->data(['title' => $slug] + $data);

        if ($updatedAt !== null) {
            $entry->set('updated_at', $updatedAt);
        }

        $entry->saveQuietly();
    }

    private function term(string $slug, ?int $updatedAt): void
    {
        $term = Term::make()->taxonomy('topics')->slug($slug)->set('title', $slug);

        if ($updatedAt !== null) {
            $term->set('updated_at', $updatedAt);
        }

        $term->saveQuietly();
    }

    #[Test]
    public function it_counts_entries_and_terms_edited_after_the_report()
    {
        config(['statamic.system.track_last_update' => true]);

        $this->entry('before', self::REPORT_AT - 60);
        $this->entry('exactly', self::REPORT_AT);
        $this->entry('after', self::REPORT_AT + 60);
        $this->term('old', self::REPORT_AT - 60);
        $this->term('new', self::REPORT_AT + 60);

        $this->assertSame(2, SeoProWidget::changesSince($this->reportAt()));
    }

    #[Test]
    public function it_skips_entries_with_a_redirect_like_the_report()
    {
        config(['statamic.system.track_last_update' => true]);

        $this->entry('page', self::REPORT_AT + 60);
        $this->entry('redirect', self::REPORT_AT + 60, ['redirect' => 'https://example.com']);

        $this->assertSame(1, SeoProWidget::changesSince($this->reportAt()));
    }

    #[Test]
    public function it_skips_content_without_updated_at()
    {
        // Saved in code only: Statamic writes updated_at in the CP, not on save().
        config(['statamic.system.track_last_update' => true]);

        $this->entry('by-code', null);
        $this->term('by-code', null);

        $this->assertSame(0, SeoProWidget::changesSince($this->reportAt()));
    }

    #[Test]
    public function it_counts_no_changes_without_last_update_tracking()
    {
        config(['statamic.system.track_last_update' => false]);

        $this->entry('page', self::REPORT_AT + 60);

        $this->assertNull(SeoProWidget::changesSince($this->reportAt()));
    }
}
