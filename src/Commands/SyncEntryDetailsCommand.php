<?php

namespace Statamic\SeoPro\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Facades\Entry;
use Statamic\SeoPro\Models\EntryLink;

class SyncEntryDetailsCommand extends Command
{
    use RunsInPlease;

    protected $signature = 'statamic:seo-pro:sync-entry-links';

    protected $description = 'Synchronizes entry titles and URIs within the Link Manager.';

    public function handle()
    {
        $this->line('Synchronizing link details...');

        EntryLink::query()->chunk(100, function ($chunk) {
            $linkEntries = $chunk->keyBy('entry_id');
            $entryIds = $chunk->pluck('entry_id')->all();
            $sourceEntries = Entry::query()->whereIn('id', $entryIds)->get(['id', 'title', 'uri'])->keyBy('id');

            foreach ($linkEntries as $entryId => $entryLink) {
                if (! $sourceEntries->has($entryId)) {
                    $entryLink->deleteQuietly();

                    continue;
                }

                $source = $sourceEntries[$entryId];

                if ($entryLink->cached_title === $source->title && $entryLink->cached_uri === $source->uri) {
                    continue;
                }

                $entryLink->cached_title = $source->title;
                $entryLink->cached_uri = $source->uri;
                $entryLink->saveQuietly();
            }
        });

        $this->info('Synchronizing link details... done!');
    }
}
