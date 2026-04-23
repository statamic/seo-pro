<?php

namespace Statamic\SeoPro\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\SeoPro\Facades\Error;

use function Laravel\Prompts\spin;

class PurgeErrorsCommand extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:seo-pro:purge-errors';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purges 404 errors older than the configured threshold.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $threshold = config('statamic.seo-pro.redirects.errors.purge_after_days', 30);

        spin(
            callback: function () use ($threshold): void {
                Error::query()
                    ->where('last_hit_at', '<', now()->subDays($threshold))
                    ->get()
                    ->each->delete();
            },
            message: 'Purging old errors...',
        );

        $this->components->success("Purged errors older than $threshold days.");
    }
}
