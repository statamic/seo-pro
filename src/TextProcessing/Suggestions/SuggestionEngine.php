<?php

namespace Statamic\SeoPro\TextProcessing\Suggestions;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\URL;
use Statamic\SeoPro\TextProcessing\Links\LinkCrawler;
use Statamic\SeoPro\TextProcessing\Models\AutomaticLink;
use Statamic\SeoPro\TextProcessing\Models\EntryLink;
use Statamic\SeoPro\TextProcessing\Similarity\Result;

class SuggestionEngine
{
    protected ?Collection $results = null;

    public function withResults(Collection $results): static
    {
        $this->results = $results;

        return $this;
    }

    protected function getPhraseContext(array $contentMapping, string $phrase): PhraseContext
    {
        $context = new PhraseContext();

        foreach ($contentMapping as $handle => $content) {
            if (! is_string($content)) {
                continue;
            }

            if (Str::contains($content, $phrase)) {
                $searchText = strip_tags($content);
                $pos = stripos($searchText, $phrase);

                if ($pos === false) {
                    continue;
                }

                $regex = '/([^.!?]*' . preg_quote($phrase, '/') . '[^.!?]*[.!?])|([^.!?]*' . preg_quote($phrase, '/') . '[^.!?]*$)/i';

                if (preg_match($regex, $searchText, $matches)) {
                    $firstMatch = trim($matches[0]);

                    if (Str::contains($firstMatch, "\n")) {
                        $lines = explode("\n", $firstMatch);

                        $curLine = '';

                        foreach ($lines as $line) {

                            if (mb_strlen($line) > mb_strlen($curLine)) {
                                $curLine = $line;
                            }

                            if (count(explode(' ', trim($line))) <= 2) {
                                continue;
                            }

                            if (Str::contains(mb_strtolower($line), $phrase)) {
                                $context->fieldHandle($handle);
                                $context->context($line);
                                $context->canReplace(true);
                                break 2;
                            }
                        }

                        $context->context($curLine);
                        break;
                    }
                }
            }
        }

        return $context;
    }

    protected function contentIndexToMapping(array $index): array
    {
        return collect($index)
            ->mapWithKeys(function ($item) {
                return [$item['fqn_path'] => $item['value']];
            })
            ->all();
    }

    public function suggest(Entry $entry)
    {
        $entryLink = EntryLink::where('entry_id', $entry->id())->firstOrFail();
        $linkResults = LinkCrawler::getLinkResultsFromEntryLink($entryLink);
        $contentMapping = self::contentIndexToMapping($entryLink->content_mapping ?? []);

        $internalLinks = [];
        $usedPhrases = [];

        foreach ($linkResults->internalLinks() as $link) {
            $internalLinks[] = URL::makeAbsolute($link['href']);
            $usedPhrases[mb_strtolower($link['text'])] = 1;
        }

        $suggestions = [];

        /** @var Result $result */
        foreach ($this->results as $result) {
            $uri = $result->entry()->uri;
            $absoluteUri = URL::makeAbsolute($uri);

            if (in_array($absoluteUri, $internalLinks)) {
                continue;
            }

            foreach ($result->similarKeywords() as $keyword => $score) {
                if (
                    array_key_exists($keyword, $suggestions) ||
                    array_key_exists($keyword, $usedPhrases)
                ) {
                   continue;
                }

                $context = $this->getPhraseContext($contentMapping, $keyword);

                $suggestions[$keyword] = [
                    'phrase' => $keyword,
                    'score' => $score,
                    'uri' => $result->entry()->uri,
                    'context' => $context->toArray(),
                    'entry' => $result->entry()->id(),
                    'auto_linked' => false,
                ];
            }
        }

        // Resolve additional details from automatic links.
        $keywordPhrases = array_keys($suggestions);

        if (count($keywordPhrases) > 0) {
            $automaticLinks = AutomaticLink::query()
                ->whereIn('link_text', $keywordPhrases)
                ->where('is_active', true)
                ->get()
                ->keyBy(fn(AutomaticLink $link) => mb_strtolower($link->link_text))
                ->all();

            foreach ($suggestions as $keyword => $suggestion) {
                if (! array_key_exists($keyword, $automaticLinks)) {
                    continue;
                }

                /** @var AutomaticLink $link */
                $link = $automaticLinks[$keyword];

                $suggestions[$keyword]['uri'] = $link->link_target;
                $suggestions[$keyword]['auto_linked'] = true;

                if ($link->entry_id) {
                    $suggestions[$keyword]['entry'] = $link->entry_id;
                }
            }
        }

        return collect($suggestions)->sortByDesc(fn($suggestion) => $suggestion['score'])->values();
    }
}