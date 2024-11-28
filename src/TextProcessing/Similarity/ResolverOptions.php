<?php

namespace Statamic\SeoPro\TextProcessing\Similarity;

class ResolverOptions
{
    public function __construct(
        public int $keywordLimit = 15,
        public int|float $keywordThreshold = 0.6,
        public bool $preventCircularLinks = false,
        public ?array $limitCollections = null,
        public ?array $limitSites = null,
    ) {}
}
