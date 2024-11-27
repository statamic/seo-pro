<?php

namespace Statamic\SeoPro\TextProcessing\Links;

use Illuminate\Support\Collection;
use Statamic\Facades\Entry as EntryApi;
use Statamic\SeoPro\Models\EntryLink;

class LinkChangeSet
{
    public function __construct(
        protected string $entryId,
        protected array $addedLinks,
        protected array $removedLinks,
    ) {}

    public function addedLinks(): array
    {
        return $this->addedLinks;
    }

    public function removedLinks(): array
    {
        return $this->removedLinks;
    }

    public function entries(): Collection
    {
        $changedUris = array_merge($this->addedLinks, $this->removedLinks);

        $entryIds = EntryLink::query()
            ->whereIn('cached_uri', $changedUris)
            ->whereNot('entry_id', $this->entryId)
            ->select('entry_id')
            ->get()
            ->pluck('entry_id')
            ->all();

        return EntryApi::query()
            ->whereIn('id', $entryIds)
            ->get();
    }
}
