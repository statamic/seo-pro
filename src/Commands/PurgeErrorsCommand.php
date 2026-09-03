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
    protected $description = 'Purges 404 errors older than the configured threshold, then purges any exceeding the configured cap.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this
            ->purgeErrorsOlderThan()
            ->purgeErrorsExceeding();
    }

    private function purgeErrorsOlderThan(): self
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

        return $this;
    }

    private function purgeErrorsExceeding(): self
    {
        $maxErrors = config('statamic.seo-pro.redirects.errors.max_errors', 0);

        if (! $maxErrors) {
            return $this;
        }

        spin(
            callback: function () use ($maxErrors): void {
                $errors = Error::query()->get();
                $excess = $errors->count() - $maxErrors;

                if ($excess <= 0) {
                    return;
                }

                $errors
                    ->sortBy([
                        fn ($a, $b) => (bool) $a->lastHitAt() <=> (bool) $b->lastHitAt(),
                        fn ($a, $b) => $a->hits() <=> $b->hits(),
                        fn ($a, $b) => $a->lastHitAt() <=> $b->lastHitAt(),
                    ])
                    ->take($excess)
                    ->each->delete();
            },
            message: 'Purging excess errors...',
        );

        $this->components->success("Purged errors exceeding the cap of $maxErrors.");

        return $this;
    }
}
