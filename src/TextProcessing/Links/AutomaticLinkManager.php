<?php

namespace Statamic\SeoPro\TextProcessing\Links;

use Illuminate\Support\Collection;
use Statamic\Facades\URL;
use Statamic\SeoPro\Contracts\TextProcessing\ConfigurationRepository;
use Statamic\SeoPro\Contracts\TextProcessing\Links\GlobalAutomaticLinksRepository;
use Statamic\SeoPro\TextProcessing\Models\AutomaticLink;
use Statamic\SeoPro\TextProcessing\Similarity\TextSimilarity;

class AutomaticLinkManager
{
    protected ?string $encoding = null;
    protected int $maxInternalLinks = 0;
    protected int $maxExternalLinks = 0;
    protected int $currentInternalLinks = 0;
    protected int $currentExternalLinks = 0;
    protected array $insertedLinks = [];

    public function __construct(
        protected readonly ConfigurationRepository $configurationRepository,
        protected readonly GlobalAutomaticLinksRepository $automaticLinks,
    ) {}

    protected function normalizeLink(string $link): string
    {
        return mb_strtolower(URL::makeAbsolute($link));
    }

    protected function shouldKeepLink(AutomaticLink $link, Collection $existingLinkTargets, array $existingLinkText): bool
    {
        if ($existingLinkTargets->has($this->normalizeLink($link->link_target))) {
            return false;
        }

        return ! TextSimilarity::similarToAny($link->link_text, $existingLinkText);
    }

    protected function filterAutomaticLinks(Collection $automaticLinks, Collection $existingLinks): Collection
    {
        $existingLinkTargets = $existingLinks
            ->pluck('href')
            ->map(fn($target) => $this->normalizeLink($target))
            ->unique()
            ->flip();

        $existingLinkText = $existingLinks
            ->pluck('text')
            ->map(fn($text) => mb_strtolower($text))
            ->unique()
            ->all();

        return $automaticLinks
            ->filter(fn($link) => $this->shouldKeepLink($link, $existingLinkTargets, $existingLinkText));
    }

    protected function exceedsLinkThreshold(int $linkCount, int $threshold): bool
    {
        if ($threshold <= 0) {
            return false;
        }

        return $linkCount > $threshold;
    }

    protected function positionIsInRange(array $range, int $pos): bool
    {
        return $pos >= $range['start'] && $pos <= $range['end'];
    }

    protected function isWithinRange(Collection $ranges, int $start, int $length): bool
    {
        $end = $start + $length;

        foreach ($ranges as $range) {
            if ($this->positionIsInRange($range, $start) || $this->positionIsInRange($range, $end)) {
                return true;
            }
        }

        return false;
    }

    protected function getFreshLinkRanges(string $content): Collection
    {
        return $this->getTextRanges(
            collect(LinkCrawler::getLinksInContent($content))->pluck('content')->all(),
            $content,
            $this->encoding
        );
    }

    protected function insertLinks(string $content, Collection $initialRanges, Collection $links, int &$currentLinkCount, int $threshold): string
    {
        $ranges = $initialRanges;

        foreach ($links as $link) {
            $normalizedLink = $this->normalizeLink($link->link_target);

            if (array_key_exists($normalizedLink, $this->insertedLinks)) {
                continue;
            }

            $linkPos = mb_stripos($content, $link->link_text);

            if (! $linkPos || $this->isWithinRange($ranges, $linkPos, str($link->link_text)->length())) {
                continue;
            }

            $content = $this->insertLink($link, $content, $linkPos);
            $this->insertedLinks[$normalizedLink] = true;

            $currentLinkCount += 1;

            if ($this->exceedsLinkThreshold($currentLinkCount, $threshold)) {
                break;
            }

            $ranges = $this->getFreshLinkRanges($content);
        }

        return $content;
    }

    protected function renderLink(AutomaticLink $link): string
    {
        return view('seo-pro::links.automatic', [
            'url' => $link->link_target,
            'text' => $link->link_text
        ])->render();
    }

    protected function insertLink(AutomaticLink $link, string $content, int $startPosition): string
    {
        return str($content)->substrReplace(
            $this->renderLink($link),
            $startPosition,
            mb_strlen($link->link_text)
        );
    }

    public function inject(string $content, string $site = 'default', ?string $encoding = null): string
    {
        $this->encoding = $encoding;
        $this->insertedLinks = [];
        $siteConfig = $this->configurationRepository->getSiteConfiguration($site);
        $linkResults = LinkCrawler::getLinkResults($content);

        $this->currentInternalLinks = count($linkResults->internalLinks());
        $this->currentExternalLinks = count($linkResults->externalLinks());

        $this->maxInternalLinks = $siteConfig->maxInternalLinks;
        $this->maxExternalLinks = $siteConfig->maxExternalLinks;

        $shouldInsertInternal = ! $this->exceedsLinkThreshold($this->currentInternalLinks, $this->maxInternalLinks);
        $shouldInsertExternal = ! $this->exceedsLinkThreshold($this->currentExternalLinks, $this->maxExternalLinks);

        if (! $shouldInsertInternal && ! $shouldInsertExternal) {
            return $content;
        }

        $allLinks = collect($linkResults->allLinks());

        $automaticLinks = $this->filterAutomaticLinks(collect($this->automaticLinks->getLinksForSite($site)), $allLinks);

        if ($automaticLinks->count() === 0) {
            return $content;
        }

        $autoInternalLinks = [];
        $autoExternalLinks = [];

        foreach ($automaticLinks as $link) {
            if (URL::isExternal($link->link_target)) {
                $autoExternalLinks[] = $link;

                continue;
            }

            $autoInternalLinks[] = $link;
        }

        $linkRanges = $this->getTextRanges($allLinks->pluck('content')->all(), $content);

        $content = $shouldInsertInternal ? $this->insertLinks($content, $linkRanges, collect($autoInternalLinks), $this->currentInternalLinks, $this->maxInternalLinks) : $content;

        return $shouldInsertExternal ? $this->insertLinks($content, $linkRanges, collect($autoExternalLinks), $this->currentExternalLinks, $this->maxExternalLinks) : $content;
    }

    protected function getTextRanges(array $needles, string $content, string $encoding = null): Collection
    {
        return collect($needles)
            ->unique()
            ->flatMap(function ($needle) use ($content, $encoding) {
                $searchLen = str($needle)->length($encoding);
                $offset = 0;

                return collect()->tap(function ($ranges) use ($content, $needle, $searchLen, &$offset, $encoding) {
                    while (($pos = str($content)->position($needle, $offset, $encoding)) !== false) {
                        $ranges->push([
                            'start' => $pos,
                            'end' => $pos + $searchLen,
                            'content' => $needle,
                        ]);

                        $offset = $pos + 1;
                    }
                });
            });
    }
}