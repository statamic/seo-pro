<?php

namespace Statamic\SeoPro\Redirects;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Statamic\SeoPro\Facades\Redirect;

class RecordRedirectHit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(public string $redirectId) {}

    public function handle(): void
    {
        Cache::lock("redirect-hit:{$this->redirectId}", 10)->block(5, function () {
            $redirect = Redirect::find($this->redirectId);

            if (! $redirect) {
                return;
            }

            $redirect
                ->hits($redirect->hits() + 1)
                ->lastHitAt(Carbon::now()->toDateTimeString())
                ->save();
        });
    }
}
