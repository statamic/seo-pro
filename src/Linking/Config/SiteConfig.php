<?php

namespace Statamic\SeoPro\Linking\Config;

readonly class SiteConfig
{
    public function __construct(
        public string $handle,
        public string $name,
        public array $ignoredPhrases,
        public int $keywordThreshold,
        public int $minInternalLinks,
        public int $maxInternalLinks,
        public int $minExternalLinks,
        public int $maxExternalLinks,
        public bool $preventCircularLinks,
    ) {}

    public function toArray(): array
    {
        return [
            'site_handle' => $this->handle,
            'ignored_phrases' => $this->ignoredPhrases,
            'keyword_threshold' => $this->keywordThreshold,
            'min_internal_links' => $this->minInternalLinks,
            'max_internal_links' => $this->maxInternalLinks,
            'min_external_links' => $this->minExternalLinks,
            'max_external_links' => $this->maxExternalLinks,
            'prevent_circular_links' => $this->preventCircularLinks,
        ];
    }

    public function __get(string $name)
    {
        return $this->toArray()[$name] ?? null;
    }
}
