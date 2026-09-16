<?php

namespace Statamic\SeoPro\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Statamic\SeoPro\DeadLinks\LinkChecker;
use Statamic\SeoPro\Facades\DeadLink;

class CheckDeadLinksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    /**
     * @param  array<string>|null  $linkIds  Specific links to check, or null to check every link.
     */
    public function __construct(protected ?array $linkIds = null) {}

    public function handle(): void
    {
        $links = is_null($this->linkIds)
            ? DeadLink::all()
            : collect($this->linkIds)->map(fn ($id) => DeadLink::find($id))->filter()->values();

        LinkChecker::checkLinks($links);
        LinkChecker::notifyIfNeeded();
    }
}
