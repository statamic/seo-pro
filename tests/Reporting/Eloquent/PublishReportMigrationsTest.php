<?php

namespace Tests\Reporting\Eloquent;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Entry;
use Statamic\Facades\Term;
use Statamic\SeoPro\Reporting\Report;
use Tests\Reporting\GeneratesContent;
use Tests\TestCase;

class PublishReportMigrationsTest extends TestCase
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

        $this->deletePublishedMigrations();
    }

    protected function tearDown(): void
    {
        $this->deletePublishedMigrations();

        parent::tearDown();
    }

    #[Test]
    public function it_publishes_the_migration_and_imports_file_reports()
    {
        $this->generateEntries(5);

        Report::create()->save()->generate();
        $this->assertFileExists(storage_path('statamic/seopro/reports/1/report.yaml'));

        $this
            ->artisan('statamic:seo-pro:database-reports')
            ->expectsConfirmation('Would you like to import existing reports?', 'yes')
            ->assertSuccessful();

        $this->assertCount(1, $this->publishedMigrations());
        $this->assertDatabaseHas('seo_pro_reports', ['id' => 1, 'status' => 'fail', 'pages_crawled' => 5]);
        $this->assertDatabaseCount('seo_pro_report_pages', 5);
    }

    #[Test]
    public function it_imports_nothing_when_there_are_no_file_reports()
    {
        $this
            ->artisan('statamic:seo-pro:database-reports')
            ->expectsConfirmation('Would you like to import existing reports?', 'yes')
            ->expectsOutputToContain('no reports to import')
            ->assertSuccessful();

        $this->assertDatabaseCount('seo_pro_reports', 0);
    }

    #[Test]
    public function it_does_not_publish_the_migration_twice()
    {
        $this
            ->artisan('statamic:seo-pro:database-reports')
            ->expectsConfirmation('Would you like to import existing reports?', 'no')
            ->assertSuccessful();

        $this
            ->artisan('statamic:seo-pro:database-reports')
            ->expectsOutputToContain('already exists')
            ->expectsConfirmation('Would you like to import existing reports?', 'no')
            ->assertSuccessful();

        $this->assertCount(1, $this->publishedMigrations());
    }

    private function publishedMigrations()
    {
        return collect($this->files->allFiles(database_path('migrations')))
            ->filter(fn ($file) => str_contains($file->getFilename(), 'create_seo_pro_reports_tables'))
            ->values();
    }

    private function deletePublishedMigrations()
    {
        $this->publishedMigrations()->each(fn ($file) => $this->files->delete($file->getPathname()));
    }
}
