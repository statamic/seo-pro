<?php

namespace Statamic\SeoPro\Content;

use DOMDocument;
use DOMXPath;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Cascade;
use Statamic\SeoPro\Contracts\Content\ContentRetriever as ContentRetrieverContract;
use Statamic\SeoPro\SeoPro;
use Statamic\SeoPro\TextProcessing\Models\EntryLink;
use Statamic\Structures\Page;

readonly class ContentRetriever implements ContentRetrieverContract
{
    public function __construct(
        protected ContentMapper $mapper,
    ) {}

    public function hashContent(string $content): string
    {
        return sha1(trim(Str::squish($content)));
    }

    protected function transformContent(string $content): string
    {
        $content = html_entity_decode($content);

        return strtr($content, [
            '’', '\'',
        ]);
    }

    protected function adjustContent(string $content, bool $stripTags = true): string
    {
        if (! $stripTags) {
            return $content;
        }

        return $this->stripTags($content);
    }

    public function stripTags(string $content): string
    {
        // Remove additional items that can cause issues with keywords, etc.
        $content = ContentRemoval::removePreCodeBlocks($content);

        return Str::squish(strip_tags($content));
    }

    public function getContent(Entry $entry, bool $stripTags = true): string
    {
        if ($entry instanceof Page) {
            $entry = $entry->entry();
        }

        $originalRequest = app('request');
        $request = tap(Request::capture(), function ($request) {
            app()->instance('request', $request);
            Cascade::withRequest($request);
        });

        try {
            $content = SeoPro::withSeoProFlag(function () use ($entry, $request) {
                return $entry->toResponse($request)->getContent();
            });
        } finally {
            app()->instance('request', $originalRequest);
        }

        if (! Str::contains($content, '<!--statamic:content-->')) {
            return '';
        }

        preg_match_all('/<!--statamic:content-->(.*?)<!--\/statamic:content-->/si', $content, $matches);

        if (! isset($matches[1]) || ! is_array($matches[1])) {
            return '';
        }

        return $this->adjustContent(
            $this->transformContent(implode('', $matches[1])),
            $stripTags
        );
    }

    public function getContentMapping(Entry $entry): array
    {
        return $this->mapper->getContentMapping($entry);
    }

    /**
     * @return array{id:string,text:string}
     */
    public function getSections(Entry $entry): array
    {
        $entryLink = EntryLink::where('entry_id', $entry->id())->first();

        if (! $entryLink) {
            return [];
        }

        $sections = [];

        $dom = new DOMDocument;

        @$dom->loadHTML($entryLink->analyzed_content);
        $xpath = new DOMXPath($dom);

        $headings = $xpath->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6');

        foreach ($headings as $heading) {
            $id = $heading->getAttribute('id');
            $name = $heading->getAttribute('name');

            if ($id) {
                $sections[] = [
                    'id' => $id,
                    'text' => trim($heading->textContent),
                ];
            } elseif ($name) {
                $sections[] = [
                    'id' => $name,
                    'text' => trim($heading->textContent),
                ];
            }
        }

        return $sections;
    }
}
