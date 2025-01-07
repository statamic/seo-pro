<?php

namespace Statamic\SeoPro\Contracts\Content;

use Statamic\Contracts\Entries\Entry;

interface ContentRetriever
{
    public function hashContent(string $content): string;

    public function getContent(Entry $entry, bool $stripTags = true): string;

    public function getContentMapping(Entry $entry): array;

    public function getSections(Entry $entry): array;

    public function stripTags(string $content): string;
}
