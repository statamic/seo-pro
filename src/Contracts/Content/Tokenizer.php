<?php

namespace Statamic\SeoPro\Contracts\Content;

interface Tokenizer
{
    public function tokenize(string $content): array;

    public function chunk(string $content, int $tokenLimit): array;
}
