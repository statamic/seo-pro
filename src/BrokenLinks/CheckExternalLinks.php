<?php

namespace Statamic\SeoPro\BrokenLinks;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Statamic\SeoPro\Facades;

class CheckExternalLinks implements ShouldQueue
{
    use Queueable;

    public function __construct(protected array $linkIds) {}

    public function handle(): void
    {
        $links = collect($this->linkIds)->map(fn ($id) => Facades\ExternalLink::find($id))->filter()->values();

        LinkChecker::checkLinks($links);
        LinkChecker::notifyIfNeeded();
    }
}
