<?php

namespace Statamic\SeoPro\Contracts\TextProcessing\Embeddings;

interface Extractor
{
    public function transform(string $content): array;

}