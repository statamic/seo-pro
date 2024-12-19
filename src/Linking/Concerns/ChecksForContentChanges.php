<?php

namespace Statamic\SeoPro\Linking\Concerns;

use Statamic\SeoPro\Models\EntryEmbedding;
use Statamic\SeoPro\Models\EntryKeyword;

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
