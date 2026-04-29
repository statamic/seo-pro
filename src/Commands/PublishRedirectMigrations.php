<?php

namespace Statamic\SeoPro\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Statamic\Console\RunsInPlease;
use Statamic\SeoPro\Facades\Redirect as RedirectFacade;
use Statamic\SeoPro\Redirects\Eloquent\RedirectModel;
use Statamic\SeoPro\Redirects\Redirect;
use Statamic\SeoPro\Redirects\RedirectRepository;
use Statamic\SeoPro\Redirects\Stache\RedirectRepository as StacheRedirectRepository;
use Statamic\Statamic;
use Statamic\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\progress;

class PublishRedirectMigrations extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:seo-pro:database-redirects';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publishes migration for the SEO Pro redirects table.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this
            ->publishMigration()
            ->runMigration()
            ->importRedirects();
    }

    private function publishMigration(): self
    {
        $name = 'create_seo_pro_redirects_table.php';

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

    private function importRedirects(): self
    {
        if (! confirm('Would you like to import existing redirects?')) {
            return $this;
        }

        Statamic::repository(RedirectRepository::class, StacheRedirectRepository::class);

        $query = RedirectFacade::query();

        $progress = progress(label: 'Importing redirects', steps: $query->count());

        $progress->start();

        $query->chunk(50, function (Collection $redirects) use ($progress) {
            $redirects->each(function (Redirect $redirect) use ($progress) {
                RedirectModel::updateOrCreate(
                    [
                        'site' => $redirect->site(),
                        'source' => $redirect->source(),
                    ],
                    [
                        'destination' => $redirect->destination(),
                        'response_code' => $redirect->responseCode(),
                        'enabled' => $redirect->enabled(),
                        'hits' => $redirect->hits(),
                        'last_hit_at' => $redirect->lastHitAt(),
                        'data' => $redirect->data(),
                    ]
                );

                $progress->advance();
            });
        });

        $progress->finish();

        $this->components->info('Redirects imported successfully.');

        return $this;
    }
}
