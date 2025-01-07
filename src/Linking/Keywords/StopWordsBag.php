<?php

namespace Statamic\SeoPro\Linking\Keywords;

class StopWordsBag
{
    public function __construct(
        public array $stopWords,
        public string $locale,
    ) {}

    public function toArray(): array
    {
        return $this->stopWords;
    }
}
