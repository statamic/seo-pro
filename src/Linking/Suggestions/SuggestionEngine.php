<?php

namespace Statamic\SeoPro\Linking\Suggestions;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\URL;
use Statamic\SeoPro\Content\ContentMapper;
use Statamic\SeoPro\Models\AutomaticLink;
use Statamic\SeoPro\Models\EntryLink;
use Statamic\SeoPro\Linking\Links\LinkCrawler;
use Statamic\SeoPro\Linking\Similarity\Result;

class SuggestionEngine
{
    protected ?Collection $results = null;

    public function __construct(
        protected readonly ContentMapper $mapper,
    ) {}

    public function withResults(Collection $results): static
    {
        $this->results = $results;

        return $this;
    }

    private function canExtractContext(mixed $value, string $phrase): bool
    {
        return is_string($value) && Str::contains($value, $phrase);
    }

    protected function getPhraseContext(array $contentMapping, string $phrase): PhraseContext
    {
        $context = new PhraseContext;

        foreach ($contentMapping as $handle => $content) {
            if (! $this->canExtractContext($content, $phrase)) {
                continue;
            }

            $searchText = strip_tags($content);
            $pos = stripos($searchText, $phrase);

            if ($pos === false) {
                continue;
            }

            $pattern = '/([^.!?]*'.preg_quote($phrase, '/').'[^.!?]*[.!?])|([^.!?]*'.preg_quote($phrase, '/').'[^.!?]*$)/i';

            if (! preg_match($pattern, $searchText, $matches)) {
                continue;
            }

            $firstMatch = trim($matches[0]);
            $matchingLine = null;

            if (Str::contains($firstMatch, "\n")) {
                $matchingLine = $this->getMatchingLine($firstMatch, $phrase);
            } else {
                $matchingLine = $this->getSurroundingWords($content, $phrase);
            }

            if (! $matchingLine) {
                continue;
            }

            $context->fieldHandle($handle);
            $context->context($matchingLine);
            $context->canReplace(true);

            break;
        }

        return $context;
    }

    protected function getMatchingLine(string $content, string $phrase)
    {
        return str($content)->explode("\n")
            ->filter(function ($line) use ($phrase) {
                return str($line)->lower()->contains($phrase) && str($line)->substrCount(' ') > 2;
            })
            ->sortByDesc(fn ($line) => str($line)->length())
            ->first();
    }

    /**
     * Attempts to locate a target phrase within a value and capture the surrounding context.
     *
     * @param  string  $content  The text to search within.
     * @param  string  $phrase  The value to search for within $content.
     * @param  int  $surroundingWords  The number of words to attempt to retrieve around the $phrase.
     */
    protected function getSurroundingWords(string $content, string $phrase, int $surroundingWords = 4): ?string
    {
        $pattern = '/(?P<before>(?:[^\s\n]+[ \t]+){0,'.$surroundingWords.'})(?P<phrase>'.preg_quote($phrase, '/').')(?P<after>(?:[ \t]+[^\s\n]+){0,'.$surroundingWords.'})/iu';

        preg_match($pattern, $content, $matches);

        if (empty($matches)) {
            return null;
        }

        return $matches['before'].$matches['phrase'].$matches['after'];
    }

    public function suggest(Entry $entry)
    {
        $entryLink = EntryLink::query()->where('entry_id', $entry->id())->firstOrFail();
        $linkResults = LinkCrawler::getLinkResultsFromEntryLink($entryLink);
        $contentMapping = $this->mapper->getContentMapping($entry);

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
                ->keyBy(fn (AutomaticLink $link) => mb_strtolower($link->link_text))
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

        return $this->filterSuggestsByReplaceable($suggestions)
            ->sortByDesc(fn ($suggestion) => $suggestion['score'])
            ->values();
    }

    protected function filterSuggestsByReplaceable(array $suggestions): Collection
    {
        $replaceable = collect($suggestions)->where('context.can_replace', true)->pluck('entry')->flip()->all();

        return collect($suggestions)->filter(function ($suggestion) use ($replaceable) {
            if (! $suggestion['context']['can_replace'] && array_key_exists($suggestion['entry'], $replaceable)) {
                return false;
            }

            return true;
        });
    }
}
