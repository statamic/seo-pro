<?php

namespace Statamic\SeoPro\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Statamic\SeoPro\BrokenLinks\LinkChecker;
use Statamic\SeoPro\Facades;

class CheckExternalLinksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(protected array $linkIds) {}

    public function handle(): void
    {
        $links = collect($this->linkIds)->map(fn ($id) => Facades\ExternalLink::find($id))->filter()->values();

        LinkChecker::checkLinks($links);
        LinkChecker::notifyIfNeeded();
    }
}
