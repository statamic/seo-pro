<?php

namespace Tests\Localized;

use Illuminate\Support\Carbon;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Statamic\Facades\Entry;
use Statamic\Facades\Term;
use Statamic\Facades\User;
use Statamic\SeoPro\Reporting\Report;
use Statamic\SeoPro\SiteDefaults\SiteDefaults;
use Statamic\SeoPro\Widgets\SeoProWidget;

class SeoProWidgetTest extends LocalizedTestCase
{
    private const REPORT_AT = 1_700_000_000;

    protected function setUp(): void
    {
        parent::setUp();

        Entry::all()->filter(fn ($entry) => $entry->hasOrigin())->each->delete();
        Entry::all()->each->delete();
        Term::all()->each->delete();

        $this->actingAs(User::make()->makeSuper()->save());
    }

    private function siteNameUrl(): ?string
    {
        $widget = new SeoProWidget;
        $widget->setConfig([]);

        $rule = ['handle' => 'SiteName', 'is_filterable' => false];

        return (new ReflectionMethod($widget, 'ruleUrl'))->invoke($widget, $rule, 'http://cool-runnings.com/cp/seo-pro/reports/1');
    }

    private function setSiteNames(array $names): void
    {
        foreach ($names as $site => $name) {
            SiteDefaults::in($site)->set('site_name', $name)->save();
        }
    }

    #[Test]
    public function the_site_name_links_to_the_first_site_without_a_name()
    {
        $this->setSiteNames([
            'default' => 'Cool Runnings',
            'french' => 'Cool Runnings FR',
            'italian' => '',
            'british' => 'Cool Runnings UK',
        ]);

        $this->assertSame('http://cool-runnings.com/cp/seo-pro/site-defaults/edit?site=italian', $this->siteNameUrl());
    }

    #[Test]
    public function the_site_name_links_to_the_selected_site_once_every_site_has_a_name()
    {
        $this->setSiteNames([
            'default' => 'Cool Runnings',
            'french' => 'Cool Runnings FR',
            'italian' => 'Corse Fantastiche',
            'british' => 'Cool Runnings UK',
        ]);

        $this->assertSame('http://cool-runnings.com/cp/seo-pro/site-defaults/edit', $this->siteNameUrl());
    }

    #[Test]
    public function it_counts_changes_in_all_sites()
    {
        config(['statamic.system.track_last_update' => true]);

        foreach (['default', 'french', 'italian'] as $site) {
            Entry::make()
                ->collection('articles')
                ->blueprint('articles')
                ->locale($site)
                ->slug('edited-'.$site)
                ->set('title', 'Edited '.$site)
                ->set('updated_at', self::REPORT_AT + 60)
                ->saveQuietly();
        }

        $report = Mockery::mock(Report::class);
        $report->shouldReceive('date')->andReturn(Carbon::createFromTimestamp(self::REPORT_AT));

        $this->assertSame(3, SeoProWidget::changesSince($report));
    }
}
