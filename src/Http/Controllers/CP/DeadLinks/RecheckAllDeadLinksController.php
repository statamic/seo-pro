<?php

namespace Statamic\SeoPro\Http\Controllers\CP\DeadLinks;

use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\DeadLinks\Link;
use Statamic\SeoPro\Facades;
use Statamic\SeoPro\Jobs\CheckDeadLinksJob;

class RecheckAllDeadLinksController extends CpController
{
    public function __invoke()
    {
        $this->authorize('manage', Link::class);

        Facades\DeadLink::query()
            ->pluck('id')
            ->chunk(config('statamic.seo-pro.dead_links.check.batch_size', 100))
            ->each(fn ($ids) => CheckDeadLinksJob::dispatch($ids->values()->all()));

        $message = config('queue.default') === 'sync'
            ? __('seo-pro::messages.dead_links_rechecked')
            : __('seo-pro::messages.dead_links_queued_for_rechecking');

        return response()->json(['message' => $message]);
    }
}
