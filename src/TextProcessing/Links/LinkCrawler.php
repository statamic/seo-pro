<?php

namespace Statamic\SeoPro\TextProcessing\Links;

use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\URL;
use Statamic\SeoPro\Contracts\TextProcessing\Links\LinkCrawler as LinkCrawlerContract;
use Statamic\SeoPro\Contracts\TextProcessing\Links\LinksRepository;
use Statamic\SeoPro\TextProcessing\EntryQuery;
use Statamic\SeoPro\TextProcessing\Models\EntryLink;
use Statamic\SeoPro\TextProcessing\Suggestions\SuggestionEngine;
use Statamic\SeoPro\TextProcessing\Suggestions\LinkResults;

readonly class LinkCrawler implements LinkCrawlerContract
{
    function __construct(
        protected SuggestionEngine $suggestionEngine,
        protected LinksRepository  $linksRepository,
    ) {}
    
    public function scanAllEntries(): void
    {
        foreach (EntryQuery::query()->lazy() as $entry) {
            $this->scanEntry($entry);
        }

        foreach (EntryQuery::query()->lazy() as $entry) {
            $this->updateInboundInternalLinkCount($entry);
        }
    }

    public function scanEntry(Entry $entry): void
    {
        $this->linksRepository->scanEntry($entry);
    }

    public static function getLinkResultsFromEntryLink(EntryLink $entryLink): LinkResults
    {
        return self::getLinkResults($entryLink->analyzed_content);
    }

    public function updateInboundInternalLinkCount(Entry $entry): void
    {
        $targetUri = $entry->uri;
        $targetLink = EntryLink::query()->where('entry_id', $entry->id)->first();

        if (! $targetLink) {
            return;
        }

        $entryLinks = EntryLink::whereJsonContains('internal_links', $targetUri)->get();
        $totalInbound = 0;

        foreach ($entryLinks as $link) {
            if (str_starts_with($link, '#')) {
                continue;
            }

            if ($link->id === $targetLink->id) {
                continue;
            }

            $linkCount = collect($link->internal_links)->filter(function ($internalLink) use ($targetUri) {
                return $internalLink === $targetUri;
            })->count();

            $totalInbound += $linkCount;
        }

        $targetLink->inbound_internal_link_count = $totalInbound;
        $targetLink->saveQuietly();
    }

    /**
     * @param string $content
     * @return array{array{href:string, content:string}}
     */
    public static function getLinksInContent(string $content): array
    {
        $links = [];
        $pattern = '/<a\s+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/i';

        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $href = $match[1];

            $links[] = [
                'href' => $href,
                'content' => $match[0]
            ];
        }

        return $links;
    }

    protected static function shouldKeepLink(string $link): bool
    {
        // Ignore self-referencing links.
        if (str_starts_with($link, '#')) {
            return false;
        }

        if (str_starts_with($link, '{') && str_ends_with($link, '}')) {
            return false;
        }

        if (str_starts_with($link, '//')) {
            return false;
        }

        return true;
    }

    public static function getLinkResults(string $content): LinkResults
    {
        $results = new LinkResults();
        $internalLinks = [];
        $externalLinks = [];

        foreach (self::getLinksInContent($content) as $link) {
            $href = $link['href'];
            $linkText = trim(strip_tags($link['content']));

            if (! self::shouldKeepLink($href)) {
                continue;
            }

            $result = [
                'href' => $href,
                'text' => $linkText,
            ];

            if (URL::isExternal($href)) {
                $externalLinks[] = $result;

                continue;
            }

            $internalLinks[] = $result;
        }

        $results->internalLinks($internalLinks);
        $results->externalLinks($externalLinks);

        return $results;
    }
}