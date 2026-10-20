<?php

namespace Tests\Reporting\Eloquent;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\Reporting\Chunk;
use Statamic\SeoPro\Reporting\Eloquent\ReportChunkModel;
use Statamic\SeoPro\Reporting\Eloquent\ReportModel;
use Statamic\SeoPro\Reporting\Eloquent\ReportPageModel;
use Statamic\SeoPro\Reporting\Eloquent\ReportRepository;
use Statamic\SeoPro\Reporting\Page;
use Statamic\SeoPro\Reporting\Report;
use Statamic\SeoPro\Reporting\ReportRepository as ReportRepositoryContract;
use Tests\TestCase;

class ReportRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = $this->app->make(ReportRepository::class);
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
    public function it_binds_the_eloquent_repository_for_the_database_driver()
    {
        $this->assertInstanceOf(ReportRepository::class, $this->app->make(ReportRepositoryContract::class));
    }

    #[Test]
    public function can_find_report()
    {
        $this->createReportModel();

        $report = $this->repo->find(1);

        $this->assertInstanceOf(Report::class, $report);
        $this->assertSame(1, $report->id());
        $this->assertSame(1700000000, $report->date()->timestamp);
        $this->assertSame('pending', $report->status());
    }

    #[Test]
    public function it_returns_null_for_unknown_ids()
    {
        $this->assertNull($this->repo->find(99));
        $this->assertNull($this->repo->find('abc'));
    }

    #[Test]
    public function can_save_report()
    {
        $this->repo->save($this->pendingReport());

        $this->assertDatabaseHas('seo_pro_reports', ['id' => 1, 'status' => 'pending']);
    }

    #[Test]
    public function can_delete_report()
    {
        $this->createReportModel();

        $this->repo->delete($this->repo->find(1));

        $this->assertDatabaseMissing('seo_pro_reports', ['id' => 1]);
    }

    #[Test]
    public function it_lists_reports_newest_first()
    {
        $this->createReportModel();
        $this->createReportModel();

        $this->assertSame([2, 1], $this->repo->all()->map->id()->all());
    }

    #[Test]
    public function it_computes_the_next_id()
    {
        $this->assertSame(1, $this->repo->nextId());

        $this->createReportModel();

        $this->assertSame(2, $this->repo->nextId());
    }

    #[Test]
    public function can_save_chunk()
    {
        $report = $this->savedReport();

        $this->repo->saveChunk(new Chunk(1, ['a', 'b'], $report));

        $this->assertDatabaseHas('seo_pro_report_chunks', ['report_id' => 1, 'chunk_id' => 1]);
        $this->assertTrue($this->repo->hasChunks($report));
    }

    #[Test]
    public function can_delete_chunk()
    {
        $report = $this->savedReport();
        $this->repo->saveChunk(new Chunk(1, ['a', 'b'], $report));

        $this->repo->deleteChunk(new Chunk(1, ['a', 'b'], $report));

        $this->assertFalse($this->repo->hasChunks($report));
    }

    #[Test]
    public function can_save_page()
    {
        $report = $this->savedReport();
        $page = new Page('entry-1', ['canonical_url' => 'http://x/a', 'site' => 'default'], $report);

        $this->repo->savePage($page);
        $this->repo->savePage($page->setResults(['SiteName' => true]));

        $this->assertDatabaseCount('seo_pro_report_pages', 1);
        $this->assertDatabaseHas('seo_pro_report_pages', ['report_id' => 1, 'page_id' => 'entry-1']);
    }

    #[Test]
    public function it_saves_a_page_in_one_query()
    {
        $report = $this->savedReport();
        $page = (new Page('entry-1', ['canonical_url' => 'http://x/a'], $report))->setResults(['SiteName' => true]);

        DB::enableQueryLog();
        $this->repo->savePage($page);
        $this->repo->savePage($page);

        $this->assertCount(2, DB::getQueryLog());
        $this->assertDatabaseCount('seo_pro_report_pages', 1);
    }

    #[Test]
    public function it_loads_pages_for_a_report()
    {
        $report = $this->savedReport();
        ReportPageModel::create([
            'report_id' => 1,
            'page_id' => 'entry-1',
            'data' => ['canonical_url' => 'http://x/a', 'site' => 'default'],
            'results' => ['SiteName' => true],
        ]);

        $pages = $this->repo->pages($report);

        $this->assertCount(1, $pages);
        $this->assertSame('entry-1', $pages->first()->id());
        $this->assertSame('http://x/a', $pages->first()->url());
        $this->assertSame(['SiteName' => true], $pages->first()->results());
        $this->assertSame($report, $pages->first()->report());
    }

    #[Test]
    public function it_deletes_pages_and_chunks_with_the_report()
    {
        $report = $this->savedReport();
        ReportPageModel::create(['report_id' => 1, 'page_id' => 'entry-1', 'data' => [], 'results' => []]);
        ReportChunkModel::create(['report_id' => 1, 'chunk_id' => 1, 'content_ids' => ['a']]);

        $this->repo->delete($report);

        $this->assertDatabaseCount('seo_pro_report_pages', 0);
        $this->assertDatabaseCount('seo_pro_report_chunks', 0);
    }

    #[Test]
    public function it_keeps_the_date_outside_utc()
    {
        $timezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Berlin');

        try {
            $this->repo->save($this->pendingReport());

            $this->assertSame(1700000000, $this->repo->find(1)->date()->timestamp);
        } finally {
            date_default_timezone_set($timezone);
        }
    }

    #[Test]
    public function it_keeps_the_newest_reports_when_deleting_old_ones()
    {
        config(['statamic.seo-pro.reports.keep_recent' => 1]);

        Report::create()->save();
        Report::create()->save();

        $this->assertSame([2], Report::all()->map->id()->all());
    }

    private function createReportModel()
    {
        return ReportModel::create([
            'date' => '2023-11-14 22:13:20',
            'status' => 'pending',
        ]);
    }

    private function pendingReport()
    {
        return Report::create()->hydrate([
            'date' => 1700000000,
            'status' => 'pending',
            'score' => null,
            'pages_crawled' => null,
            'pages_actionable' => null,
            'results' => null,
        ]);
    }

    private function savedReport()
    {
        $report = $this->pendingReport();

        $this->repo->save($report);

        return $report;
    }
}
