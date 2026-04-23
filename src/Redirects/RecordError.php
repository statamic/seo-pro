<?php

namespace Statamic\SeoPro\Redirects;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Statamic\SeoPro\Facades\Error;

class RecordError implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(public string $url) {}

    public function handle(): void
    {
        Cache::lock("error:{$this->url}", 10)->block(5, function () {
            $error = Error::query()->where('url', $this->url)->first();

            if (! $error) {
                $error = Error::make()->url($this->url);
            }

            $error
                ->hits($error->hits() + 1)
                ->lastHitAt(Carbon::now()->toDateTimeString())
                ->save();
        });
    }
}
