<?php

namespace Tests\Reporting\Eloquent;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Entry;
use Statamic\Facades\Term;
use Statamic\SeoPro\Reporting\Report;
use Tests\Reporting\GeneratesContent;
use Tests\TestCase;

class GenerateReportTest extends TestCase
{
    use GeneratesContent;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Entry::all()->each->delete();
        Term::all()->each->delete();

        if ($this->files->exists($path = storage_path('statamic/seopro/reports'))) {
            $this->files->deleteDirectory($path);
        }
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('statamic.seo-pro.reports.driver', 'database');
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../../../src/Commands/stubs');
    }

    #[Test]
    public function it_generates_a_report_into_the_database()
    {
        $this->generateEntries(5)->generateTerms(5);

        Report::create()->save()->generate();

        $this->assertDatabaseHas('seo_pro_reports', ['id' => 1, 'status' => 'fail', 'pages_crawled' => 10, 'pages_actionable' => 10]);
        $this->assertDatabaseCount('seo_pro_report_pages', 10);
        $this->assertDatabaseCount('seo_pro_report_chunks', 0);

        $report = Report::find(1);
        $this->assertSame(86.0, $report->score());
        $this->assertSame('fail', $report->status());
        $this->assertTrue($report->isGenerated());
        $this->assertCount(10, $report->withPages()->pages());
        $this->assertSame('fail', $report->pages()->first()->status());
        $this->assertFileDoesNotExist(storage_path('statamic/seopro/reports'));
    }

    #[Test]
    public function it_generates_a_large_report_in_chunks()
    {
        config(['statamic.seo-pro.reports.queue_chunk_size' => 3]);

        $this->generateEntries(7);

        Report::create()->save()->generate();

        $this->assertDatabaseCount('seo_pro_report_pages', 7);
        $this->assertDatabaseCount('seo_pro_report_chunks', 0);
        $this->assertTrue(Report::find(1)->isGenerated());
        $this->assertSame(7, Report::find(1)->pagesCrawled());
    }
}
