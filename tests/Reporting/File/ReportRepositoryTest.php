<?php

namespace Tests\Reporting\File;

use PHPUnit\Framework\Attributes\Test;
use Statamic\SeoPro\Reporting\Chunk;
use Statamic\SeoPro\Reporting\File\ReportRepository;
use Statamic\SeoPro\Reporting\Page;
use Statamic\SeoPro\Reporting\Report;
use Statamic\SeoPro\Reporting\ReportRepository as ReportRepositoryContract;
use Tests\TestCase;

class ReportRepositoryTest extends TestCase
{
    protected $repo;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->files->exists($path = $this->reportsPath())) {
            $this->files->deleteDirectory($path);
        }

        $this->repo = $this->app->make(ReportRepository::class);
    }

    protected function tearDown(): void
    {
        if ($this->files->exists($path = $this->elsewherePath())) {
            $this->files->deleteDirectory($path);
        }

        parent::tearDown();
    }

    #[Test]
    public function it_binds_the_file_repository_by_default()
    {
        $this->assertInstanceOf(ReportRepository::class, $this->app->make(ReportRepositoryContract::class));
    }

    #[Test]
    public function can_find_report()
    {
        $this->repo->save($this->pendingReport());

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

        $this->assertFileExists($this->reportsPath('1/report.yaml'));
    }

    #[Test]
    public function can_delete_report()
    {
        $report = $this->savedReport();

        $this->repo->delete($report);

        $this->assertFileDoesNotExist($this->reportsPath('1'));
    }

    #[Test]
    public function it_lists_reports_newest_first()
    {
        $this->repo->save($this->pendingReport());
        $this->repo->save($this->pendingReport());

        $this->assertSame([2, 1], $this->repo->all()->map->id()->all());
    }

    #[Test]
    public function it_computes_the_next_id()
    {
        $this->assertSame(1, $this->repo->nextId());

        $this->repo->save($this->pendingReport());

        $this->assertSame(2, $this->repo->nextId());
    }

    #[Test]
    public function it_reads_the_directory_from_config()
    {
        config(['statamic.seo-pro.reports.directory' => $this->elsewherePath()]);

        $this->repo->save($this->pendingReport());

        $this->assertFileExists($this->elsewherePath('1/report.yaml'));
    }

    #[Test]
    public function it_falls_back_to_the_default_directory_when_the_key_is_missing()
    {
        // A config published before the directory key existed has a reports section without it.
        config(['statamic.seo-pro.reports' => ['keep_recent' => 'all', 'queue_chunk_size' => 1000]]);

        $this->repo->save($this->pendingReport());

        $this->assertFileExists($this->reportsPath('1/report.yaml'));
        $this->assertCount(1, $this->repo->all());
    }

    #[Test]
    public function can_save_chunk()
    {
        $report = $this->savedReport();

        $this->repo->saveChunk(new Chunk(1, ['a', 'b'], $report));

        $this->assertFileExists($this->reportsPath('1/chunks/1/chunk.yaml'));
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

        $this->repo->savePage((new Page('entry-1', ['canonical_url' => 'http://x/a', 'site' => 'default'], $report))->setResults(['SiteName' => true]));

        $this->assertCount(1, $this->files->allFiles($this->reportsPath('1/pages')));
    }

    #[Test]
    public function it_loads_pages_for_a_report()
    {
        $report = $this->savedReport();
        $this->repo->savePage((new Page('entry-1', ['canonical_url' => 'http://x/a', 'site' => 'default'], $report))->setResults(['SiteName' => true]));

        $pages = $this->repo->pages($report);

        $this->assertCount(1, $pages);
        $this->assertSame('entry-1', $pages->first()->id());
        $this->assertSame('http://x/a', $pages->first()->url());
        $this->assertSame(['SiteName' => true], $pages->first()->results());
        $this->assertSame($report, $pages->first()->report());
    }

    private function reportsPath($append = null)
    {
        return collect([storage_path('statamic/seopro/reports'), $append])->filter()->implode('/');
    }

    private function elsewherePath($append = null)
    {
        return collect([storage_path('statamic/seopro/elsewhere'), $append])->filter()->implode('/');
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
