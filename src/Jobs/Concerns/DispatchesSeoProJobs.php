<?php

namespace Statamic\SeoPro\Jobs\Concerns;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

trait DispatchesSeoProJobs
{
    use Queueable, InteractsWithQueue, Dispatchable;

    public static function dispatchSeoProJob(...$args): void
    {
        $connection = config('statamic.seo-pro.jobs.connection');
        $queue = config('statamic.seo-pro.jobs.queue');

        if (! $connection) {
            self::dispatchSync(...$args);

            return;
        }

        self::dispatch(...$args)
            ->onConnection($connection)
            ->onQueue($queue ?? 'default');
    }
}