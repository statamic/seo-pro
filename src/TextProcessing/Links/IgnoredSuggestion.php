<?php

namespace Statamic\SeoPro\TextProcessing\Links;

readonly class IgnoredSuggestion
{
    public function __construct(
        public string $action,
        public string $scope,
        public string $phrase,
        public string $entry,
        public string $ignoredEntry,
        public string $site,
    ) {}
}
