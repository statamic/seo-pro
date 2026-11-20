<?php

namespace Statamic\SeoPro\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Statamic\Console\RunsInPlease;
use Statamic\SeoPro\Reporting\Eloquent\ReportRepository as EloquentReportRepository;
use Statamic\SeoPro\Reporting\File\ReportRepository as FileReportRepository;
use Statamic\SeoPro\Reporting\Report;
use Statamic\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\progress;

class PublishReportMigrations extends Command
{
    use RunsInPlease;

    protected $signature = 'statamic:seo-pro:database-reports';

    protected $description = 'Publishes migration for the SEO Pro reports tables.';

    public function handle()
    {
        $this
            ->publishMigration()
            ->runMigration()
            ->importReports();
    }

    private function publishMigration(): self
    {
        $name = 'create_seo_pro_reports_tables.php';

        $existingMigration = collect(File::allFiles(database_path('migrations')))
            ->map->getFilename()
            ->filter(fn (string $filename) => Str::contains($filename, $name))
            ->first();

        if ($existingMigration) {
            $this->components->info("Migration [database/migrations/{$existingMigration}] already exists.");

            return $this;
        }

        $filename = date('Y_m_d_His').'_'.$name;

        $contents = File::get(__DIR__.'/stubs/'.$name);

        File::put(database_path('migrations/'.$filename), $contents);

        $this->components->info("Migration [database/migrations/{$filename}] published successfully.");

        return $this;
    }

    private function runMigration(): self
    {
        Artisan::call('migrate', ['--force' => true], $this->output);

        $this->newLine();

        return $this;
    }

    private function importReports(): self
    {
        if (! confirm('Would you like to import existing reports?')) {
            return $this;
        }

        $files = app(FileReportRepository::class);
        $database = app(EloquentReportRepository::class);

        $reports = $files->all();

        if ($reports->isEmpty()) {
            $this->components->info('There are no reports to import.');

            return $this;
        }

        $progress = progress(label: 'Importing reports', steps: $reports->count());

        $progress->start();

        $reports->each(function (Report $report) use ($files, $database, $progress) {
            $database->save($report);

            $files->pages($report)->each(fn ($page) => $database->savePage($page));

            $progress->advance();
        });

        $progress->finish();

        $this->components->info('Reports imported successfully.');

        return $this;
    }
}
