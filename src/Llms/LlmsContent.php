<?php

namespace Statamic\SeoPro\Llms;

use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Sites\Site as SiteObject;

class LlmsContent
{
    public function sections(LlmsDocument|array $document, string|SiteObject|null $site = null): array
    {
        $values = $document instanceof LlmsDocument
            ? $document->all()
            : Llms::withDefaults($document);
        $site = Llms::site($site);
        $sections = [];
        $included = [];

        foreach ($values['collections'] as $handle) {
            $collection = Collection::find($handle);

            if (! $collection || ! $collection->sites()->contains($site->handle())) {
                continue;
            }

            $entries = $collection->queryEntries()
                ->where('site', $site->handle())
                ->whereNotNull('uri')
                ->whereStatus('published')
                ->orderBy('uri')
                ->get();

            foreach ($entries as $entry) {
                $this->addEntry($sections, $included, $entry);
            }
        }

        foreach ($values['entries'] as $id) {
            $entry = EntryFacade::find($id)?->in($site->handle());

            if (! $entry || $entry->status() !== 'published' || $entry->uri() === null) {
                continue;
            }

            $this->addEntry($sections, $included, $entry);
        }

        return collect($sections)
            ->filter(fn (array $section) => $section['links'] !== [])
            ->values()
            ->all();
    }

    private function addEntry(array &$sections, array &$included, Entry $entry): void
    {
        if (isset($included[$entry->id()])) {
            return;
        }

        $url = $entry->absoluteUrl();

        if (! is_string($url) || $url === '') {
            return;
        }

        $collection = $entry->collection();
        $handle = $collection->handle();
        $title = $entry->value('title');
        $title = is_scalar($title) || $title instanceof \Stringable
            ? trim((string) preg_replace('/\s+/u', ' ', (string) $title))
            : '';

        if ($title === '') {
            $title = (string) ($entry->slug() ?? $entry->id());
        }

        $sections[$handle] ??= [
            'title' => trim((string) preg_replace('/\s+/u', ' ', $collection->title())),
            'links' => [],
            'parse_antlers' => false,
        ];
        $sections[$handle]['links'][] = [
            'title' => $title,
            'url' => $url,
            'description' => '',
        ];
        $included[$entry->id()] = true;
    }
}
