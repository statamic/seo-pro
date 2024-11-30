<?php

namespace Statamic\SeoPro\Contracts\Linking\Embeddings;

interface Extractor
{
    public function transform(string $content): array;
}
