<?php

namespace Tests;

use Illuminate\Support\Carbon;
use Statamic\Facades\Blink;
use Statamic\Facades\Config;
use Statamic\Facades\Entry;
use Statamic\Facades\Term;
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
        $this->assertFileNotExists($this->reportsPath());

        Carbon::setTestNow($now = now());
        Report::create()->save();

        $this->assertCount(1, $this->files->directories($this->reportsPath()));
        $this->assertFileExists($this->reportsPath('1'));

        $expected = <<<"EXPECTED"
date: $now->timestamp
status: pending
score: null
pages_crawled: null
results: null

EXPECTED;

        $this->assertCount(1, $this->files->allFiles($this->reportsPath('1')));
        $this->assertEquals($expected, $this->files->get($this->reportsPath('1/report.yaml')));
    }

    /** @test */
    public function it_increments_report_folder_numbers()
    {
        $this->assertFileNotExists($this->reportsPath());

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

        $this->assertFileNotExists($this->reportsPath());

        Carbon::setTestNow($now = now());
        Report::create()->save()->generate();

        $expected = <<<"EXPECTED"
date: $now->timestamp
status: fail
score: 75.0
pages_crawled: 10
results:
  SiteName: true
  UniqueTitleTag: 0
  UniqueMetaDescription: 10
  NoUnderscoresInUrl: 0
  ThreeSegmentUrls: 0

EXPECTED;

        $this->assertCount(1, $this->files->files($this->reportsPath('1')));
        $this->assertEquals($expected, $this->files->get($this->reportsPath('1/report.yaml')));

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

        $this->assertFileNotExists($this->reportsPath());

        // Bind our test chunk class so we can detect how many are generated.
        app()->bind(Chunk::class, TestChunk::class);

        Carbon::setTestNow($now = now());
        Report::create()->save()->generate();

        // Assert we saved exactly four chunks, based on our above config, and the number of pages being generated.
        $this->assertEquals(4, Blink::get('saving-chunk'));

        $expected = <<<"EXPECTED"
date: $now->timestamp
status: fail
score: 75.0
pages_crawled: 10
results:
  SiteName: true
  UniqueTitleTag: 0
  UniqueMetaDescription: 10
  NoUnderscoresInUrl: 0
  ThreeSegmentUrls: 0

EXPECTED;

        $this->assertFileNotExists($this->reportsPath('1/chunks'));

        $this->assertCount(1, $this->files->files($this->reportsPath('1')));
        $this->assertEquals($expected, $this->files->get($this->reportsPath('1/report.yaml')));

        $this->assertFileExists($this->reportsPath('1/pages'));
        $this->assertCount(10, $this->files->allFiles($this->reportsPath('1/pages')));
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

    /**
     * Normalize line endings before performing assertion in windows.
     */
    public static function assertEquals($needle, $haystack, $message = ''): void
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
