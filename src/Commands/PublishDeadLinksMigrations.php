<?php

namespace Statamic\SeoPro\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Statamic\Console\RunsInPlease;
use Statamic\SeoPro\DeadLinks\Eloquent\LinkModel;
use Statamic\SeoPro\DeadLinks\Link;
use Statamic\SeoPro\DeadLinks\LinkRepository;
use Statamic\SeoPro\DeadLinks\Stache\LinkRepository as StacheLinkRepository;
use Statamic\SeoPro\Facades\DeadLink as DeadLinkFacade;
use Statamic\Statamic;
use Statamic\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\progress;

class PublishDeadLinksMigrations extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:seo-pro:database-dead-links';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publishes migration for the SEO Pro dead links table.';

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
            ->importLinks();
    }

    private function publishMigration(): self
    {
        $name = 'create_seo_pro_dead_links_table.php';

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

    private function importLinks(): self
    {
        if (! confirm('Would you like to import existing tracked dead links?')) {
            return $this;
        }

        Statamic::repository(LinkRepository::class, StacheLinkRepository::class);

        $query = DeadLinkFacade::query();

        $progress = progress(label: 'Importing dead links', steps: $query->count());

        $progress->start();

        $query->chunk(50, function (Collection $links) use ($progress) {
            $links->each(function (Link $link) use ($progress) {
                LinkModel::updateOrCreate(
                    ['url' => $link->url()],
                    [
                        'site' => $link->site(),
                        'status' => $link->status(),
                        'status_code' => $link->statusCode(),
                        'error' => $link->error(),
                        'consecutive_failures' => $link->consecutiveFailures(),
                        'checked_at' => $link->checkedAt(),
                        'next_check_at' => $link->nextCheckAt(),
                        'notified_at' => $link->notifiedAt(),
                        'references' => $link->references()->all(),
                        'data' => $link->data(),
                    ]
                );

                $progress->advance();
            });
        });

        $progress->finish();

        $this->components->info('Dead links imported successfully.');

        return $this;
    }
}
