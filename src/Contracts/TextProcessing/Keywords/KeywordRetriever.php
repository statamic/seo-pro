<?php

namespace Statamic\SeoPro\Contracts\TextProcessing\Keywords;

use Illuminate\Support\Collection;

interface KeywordRetriever
{
    public function inLocale(string $locale): static;

    public function getStopWords(): array;

    public function extractKeywords(string $content): Collection;

    public function extractRankedKeywords(string $content): Collection;
}
