<?php

namespace Tests;

use Illuminate\Support\Carbon;
use Statamic\Facades\Blink;
use Statamic\Facades\Config;
use Statamic\Facades\Entry;
use Statamic\Facades\Term;
use Statamic\Facades\YAML;
use Statamic\SeoPro\Reporting\Chunk;
use Statamic\SeoPro\Reporting\Report;
use Statamic\Support\Str;

class ReportTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Entry::all()->each->delete();
        Term::all()->each->delete();

        if ($this->files->exists($path = $this->reportsPath())) {
            $this->files->deleteDirectory($path);
        }
    }

    /** @test */
    public function it_can_save_pending_report()
    {
        $this->assertFileDoesNotExist($this->reportsPath());

        Carbon::setTestNow($now = now());
        Report::create()->save();

        $this->assertCount(1, $this->files->directories($this->reportsPath()));
        $this->assertFileExists($this->reportsPath('1'));

        $expected = <<<"EXPECTED"
date: $now->timestamp
status: pending
score: null
pages_crawled: null
pages_actionable: null
results: null

EXPECTED;

        $this->assertCount(1, $this->files->allFiles($this->reportsPath('1')));
        $this->assertEqualsIgnoringLineEndings($expected, $this->files->get($this->reportsPath('1/report.yaml')));
    }

    /** @test */
    public function it_increments_report_folder_numbers()
    {
        $this->assertFileDoesNotExist($this->reportsPath());

        Report::create()->save();
        Report::create()->save();
        Report::create()->save();

        $this->assertCount(3, $this->files->directories($this->reportsPath()));
        $this->assertFileExists($this->reportsPath('1'));
        $this->assertFileExists($this->reportsPath('2'));
        $this->assertFileExists($this->reportsPath('3'));
    }

    /** @test */
    public function it_can_generate_a_report()
    {
        $this
            ->generateEntries(5)
            ->generateTerms(5);

        $this->assertFileDoesNotExist($this->reportsPath());

        Carbon::setTestNow($now = now());
        Report::create()->save()->generate();

        $expected = <<<"EXPECTED"
date: $now->timestamp
status: fail
score: 75.0
pages_crawled: 10
pages_actionable: 10
results:
  SiteName: true
  UniqueTitleTag: 0
  UniqueMetaDescription: 10
  NoUnderscoresInUrl: 0
  ThreeSegmentUrls: 0

EXPECTED;

        $this->assertCount(1, $this->files->files($this->reportsPath('1')));
        $this->assertEqualsIgnoringLineEndings($expected, $this->files->get($this->reportsPath('1/report.yaml')));

        $this->assertFileExists($this->reportsPath('1/pages'));
        $this->assertCount(10, $this->files->allFiles($this->reportsPath('1/pages')));
    }

    /** @test */
    public function it_doesnt_delete_old_reports_when_generating_by_default()
    {
        $this->generateEntries(1);

        $this->assertFileDoesNotExist($this->reportsPath());

        Carbon::setTestNow($now = now());

        foreach (range(1, 17) as $i) {
            Report::create()->save()->generate();
        }

        $this->assertCount(17, $this->files->directories($this->reportsPath()));
    }

    /** @test */
    public function it_doesnt_delete_old_reports_when_explicit_all_is_configured()
    {
        Config::set('statamic.seo-pro.reports.keep_recent', 'all');

        $this->generateEntries(1);

        $this->assertFileDoesNotExist($this->reportsPath());

        Carbon::setTestNow($now = now());

        foreach (range(1, 17) as $i) {
            Report::create()->save()->generate();
        }

        $this->assertCount(17, $this->files->directories($this->reportsPath()));
    }

    /** @test */
    public function it_can_delete_old_reports_when_generating()
    {
        Config::set('statamic.seo-pro.reports.keep_recent', 13);

        $this->generateEntries(1);

        $this->assertFileDoesNotExist($this->reportsPath());

        Carbon::setTestNow($now = now());

        foreach (range(1, 17) as $i) {
            Report::create()->save()->generate();
        }

        $this->assertCount(13, $this->files->directories($this->reportsPath()));
        $this->assertFileDoesNotExist($this->reportsPath('1'));
        $this->assertFileDoesNotExist($this->reportsPath('2'));
        $this->assertFileDoesNotExist($this->reportsPath('3'));
        $this->assertFileDoesNotExist($this->reportsPath('4'));
        $this->assertFileExists($this->reportsPath('5'));
        $this->assertFileExists($this->reportsPath('6'));
        $this->assertFileExists($this->reportsPath('7'));
        $this->assertFileExists($this->reportsPath('8'));
        $this->assertFileExists($this->reportsPath('9'));
        $this->assertFileExists($this->reportsPath('10'));
        $this->assertFileExists($this->reportsPath('11'));
        $this->assertFileExists($this->reportsPath('12'));
        $this->assertFileExists($this->reportsPath('13'));
        $this->assertFileExists($this->reportsPath('14'));
        $this->assertFileExists($this->reportsPath('15'));
        $this->assertFileExists($this->reportsPath('16'));
        $this->assertFileExists($this->reportsPath('17'));
    }

    /** @test */
    public function it_properly_calculates_actionable_count()
    {
        $this
            ->generateEntries(5)
            ->generateTerms(5);

        Entry::all()
            ->each(fn ($entry, $id) => $entry->set('seo', ['description' => 'Custom Entry Description'.$id])->save())
            ->take(2)
            ->each(fn ($entry) => $entry->set('seo', array_merge($entry->get('seo'), ['description' => 'Fail Description']))->save());

        Term::all()
            ->each(fn ($term, $id) => $term->set('seo', ['description' => 'Custom Term Description'.$id])->save())
            ->take(4)
            ->each(fn ($term) => $term->set('seo', array_merge($term->get('seo'), ['title' => 'Fail Title']))->save());

        $this->assertFileDoesNotExist($this->reportsPath());

        Carbon::setTestNow($now = now());
        Report::create()->save()->generate();

        $expected = <<<"EXPECTED"
date: $now->timestamp
status: fail
score: 75.0
pages_crawled: 10
pages_actionable: 6
results:
  SiteName: true
  UniqueTitleTag: 4
  UniqueMetaDescription: 2
  NoUnderscoresInUrl: 0
  ThreeSegmentUrls: 0

EXPECTED;

        $this->assertCount(1, $this->files->files($this->reportsPath('1')));
        $this->assertEqualsIgnoringLineEndings($expected, $this->files->get($this->reportsPath('1/report.yaml')));

        $this->assertFileExists($this->reportsPath('1/pages'));
        $this->assertCount(10, $this->files->allFiles($this->reportsPath('1/pages')));
    }

    /** @test */
    public function it_can_generate_a_large_report_with_multiple_chunked_jobs()
    {
        Config::set('statamic.seo-pro.reports.queue_chunk_size', 3);

        $this
            ->generateEntries(5)
            ->generateTerms(5);

        $this->assertFileDoesNotExist($this->reportsPath());

        // Bind our test chunk class so we can detect how many are generated.
        app()->bind(Chunk::class, TestChunk::class);

        Carbon::setTestNow($now = now());
        Report::create()->save()->generate();

        // Assert we saved exactly four chunks, based on our above config, and the number of pages being generated.
        $this->assertEqualsIgnoringLineEndings(4, Blink::get('saving-chunk'));

        $expected = <<<"EXPECTED"
date: $now->timestamp
status: fail
score: 75.0
pages_crawled: 10
pages_actionable: 10
results:
  SiteName: true
  UniqueTitleTag: 0
  UniqueMetaDescription: 10
  NoUnderscoresInUrl: 0
  ThreeSegmentUrls: 0

EXPECTED;

        $this->assertFileDoesNotExist($this->reportsPath('1/chunks'));

        $this->assertCount(1, $this->files->files($this->reportsPath('1')));
        $this->assertEqualsIgnoringLineEndings($expected, $this->files->get($this->reportsPath('1/report.yaml')));

        $this->assertFileExists($this->reportsPath('1/pages'));
        $this->assertCount(10, $this->files->allFiles($this->reportsPath('1/pages')));
    }

    /** @test */
    public function it_skips_over_pages_with_disabled_seo()
    {
        $this
            ->generateEntries(5)
            ->generateTerms(5);

        $this->assertFileDoesNotExist($this->reportsPath());

        Entry::all()->first()->set('seo', false)->save();

        Carbon::setTestNow($now = now());
        Report::create()->save()->generate();

        $expected = <<<"EXPECTED"
date: $now->timestamp
status: fail
score: 76.0
pages_crawled: 9
pages_actionable: 9
results:
  SiteName: true
  UniqueTitleTag: 0
  UniqueMetaDescription: 9
  NoUnderscoresInUrl: 0
  ThreeSegmentUrls: 0

EXPECTED;

        $this->assertCount(1, $this->files->files($this->reportsPath('1')));
        $this->assertEqualsIgnoringLineEndings($expected, $this->files->get($this->reportsPath('1/report.yaml')));

        $this->assertFileExists($this->reportsPath('1/pages'));
        $this->assertCount(9, $this->files->allFiles($this->reportsPath('1/pages')));
    }

    /** @test */
    public function it_skips_over_entries_with_redirects()
    {
        $this->generateEntries(5);

        $this->assertFileDoesNotExist($this->reportsPath());

        Entry::all()->first()->set('redirect', 'https://statamic.com')->save();

        Carbon::setTestNow($now = now());
        Report::create()->save()->generate();

        $expected = <<<"EXPECTED"
date: $now->timestamp
status: fail
score: 82.0
pages_crawled: 4
pages_actionable: 4
results:
  SiteName: true
  UniqueTitleTag: 0
  UniqueMetaDescription: 4
  NoUnderscoresInUrl: 0
  ThreeSegmentUrls: 0

EXPECTED;

        $this->assertCount(1, $this->files->files($this->reportsPath('1')));
        $this->assertEqualsIgnoringLineEndings($expected, $this->files->get($this->reportsPath('1/report.yaml')));

        $this->assertFileExists($this->reportsPath('1/pages'));
        $this->assertCount(4, $this->files->allFiles($this->reportsPath('1/pages')));
    }

    /** @test */
    public function it_properly_reports_on_unique_custom_title_values()
    {
        $this->generateEntries(5);

        Entry::all()
            ->take(2)
            ->each(fn ($entry, $id) => $entry->set('seo', ['title' => 'Custom Title'.$id])->save());

        $this->assertEqualsIgnoringLineEndings(0, $this->getReportResult('UniqueTitleTag'));
    }

    /** @test */
    public function it_properly_reports_on_unique_custom_description_values()
    {
        $this->generateEntries(5);

        Entry::all()
            ->each(fn ($entry, $id) => $entry->set('seo', ['description' => 'Custom Description'.$id])->save());

        $this->assertEqualsIgnoringLineEndings(0, $this->getReportResult('UniqueMetaDescription'));
    }

    public function reportsPath($path = null)
    {
        if ($path) {
            $path = Str::ensureLeft($path, '/');
        }

        return storage_path('statamic/seopro/reports').$path;
    }

    protected function generateEntries($count)
    {
        collect(range(1, $count))->each(function ($i) {
            Entry::make()
                ->collection('articles')
                ->blueprint('article')
                ->slug('test-entry-'.$i)
                ->set('title', 'Test Entry '.$i)
                ->save();
        });

        return $this;
    }

    protected function generateTerms($count)
    {
        collect(range(1, $count))->each(function ($i) {
            Term::make()
                ->slug($slug = 'test-term-'.$i)
                ->taxonomy('topics')
                ->set('title', 'Test Term '.$i)
                ->save();
        });

        return $this;
    }

    protected function getReportResult($key)
    {
        Carbon::setTestNow($now = now());

        Report::create()->save()->generate();

        return YAML::file($this->reportsPath('1/report.yaml'))->parse()['results'][$key];
    }

    /**
     * Normalize line endings before performing assertion in windows.
     */
    public static function assertEqualsIgnoringLineEndings($needle, $haystack, $message = ''): void
    {
        parent::assertEquals(
            is_string($needle) ? static::normalizeMultilineString($needle) : $needle,
            is_string($haystack) ? static::normalizeMultilineString($haystack) : $haystack,
            $message
        );
    }
}

class TestChunk extends Chunk
{
    public function save()
    {
        Blink::increment('saving-chunk');

        return parent::save();
    }
}
