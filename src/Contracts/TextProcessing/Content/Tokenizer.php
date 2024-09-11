<?php

namespace Statamic\SeoPro\Contracts\TextProcessing\Content;

interface Tokenizer
{
    public function tokenize(string $content): array;

    public function chunk(string $content, int $tokenLimit): array;
}