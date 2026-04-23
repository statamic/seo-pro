<?php

namespace Statamic\SeoPro\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Statamic\Console\RunsInPlease;
use Statamic\SeoPro\Facades\Error as ErrorFacade;
use Statamic\SeoPro\Redirects\Eloquent\ErrorModel;
use Statamic\SeoPro\Redirects\Error;
use Statamic\SeoPro\Redirects\ErrorRepository;
use Statamic\SeoPro\Redirects\Stache\ErrorRepository as StacheErrorRepository;
use Statamic\Statamic;
use Statamic\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\progress;

class PublishErrorMigrations extends Command
{
    use RunsInPlease;

    protected $signature = 'statamic:seo-pro:database-errors';

    protected $description = 'Publishes migration for the SEO Pro redirect errors table.';

    public function handle()
    {
        $this
            ->publishMigration()
            ->runMigration()
            ->importErrors();
    }

    private function publishMigration(): self
    {
        $name = 'create_errors_table.php';

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

    private function importErrors(): self
    {
        if (! confirm('Would you like to import existing errors?')) {
            return $this;
        }

        Statamic::repository(ErrorRepository::class, StacheErrorRepository::class);

        $query = ErrorFacade::query();

        $progress = progress(label: 'Importing errors', steps: $query->count());

        $progress->start();

        $query->chunk(50, function (Collection $errors) use ($progress) {
            $errors->each(function (Error $error) use ($progress) {
                ErrorModel::updateOrCreate(
                    [
                        'site' => $error->site(),
                        'url' => $error->url(),
                    ],
                    [
                        'hits' => $error->hits(),
                        'last_hit_at' => $error->lastHitAt(),
                        'data' => $error->data(),
                    ]
                );

                $progress->advance();
            });
        });

        $progress->finish();

        $this->components->info('Errors imported successfully.');

        return $this;
    }
}
