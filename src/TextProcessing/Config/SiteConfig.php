<?php

namespace Statamic\SeoPro\TextProcessing\Config;

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
    ) {}

    public function toArray(): array
    {
        return [
            'handle' => $this->handle,
            'name' => $this->name,
            'ignored_phrases' => $this->ignoredPhrases,
            'keyword_threshold' => $this->keywordThreshold,
            'min_internal_links' => $this->minInternalLinks,
            'max_internal_links' => $this->maxInternalLinks,
            'min_external_links' => $this->minExternalLinks,
            'max_external_links' => $this->maxExternalLinks,
        ];
    }
}
