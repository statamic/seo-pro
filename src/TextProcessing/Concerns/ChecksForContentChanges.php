<?php

namespace Statamic\SeoPro\TextProcessing\Concerns;

use Statamic\SeoPro\TextProcessing\Models\EntryEmbedding;
use Statamic\SeoPro\TextProcessing\Models\EntryKeyword;

trait ChecksForContentChanges
{
    protected function isContentSame(EntryKeyword|EntryEmbedding $model, string $content): bool
    {
        if (! $model->exists) {
            return false;
        }

        return $this->contentRetriever->hashContent($content) === $model->content_hash;
    }
}