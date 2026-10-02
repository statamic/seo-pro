<?php

namespace Statamic\SeoPro\Http\Controllers\CP\BrokenLinks;

use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\BrokenLinks\CheckExternalLinks;
use Statamic\SeoPro\BrokenLinks\ExternalLink;
use Statamic\SeoPro\Facades;

class RecheckAllBrokenLinksController extends CpController
{
    public function __invoke()
    {
        $this->authorize('index', ExternalLink::class);

        Facades\ExternalLink::query()
            ->pluck('id')
            ->chunk(config('statamic.seo-pro.broken_links.check.batch_size', 100))
            ->each(fn ($ids) => CheckExternalLinks::dispatch($ids->values()->all()));

        $message = config('queue.default') === 'sync'
            ? __('seo-pro::messages.broken_links_rechecked')
            : __('seo-pro::messages.broken_links_queued_for_rechecking');

        return response()->json(['message' => $message]);
    }
}
