<?php

namespace Statamic\SeoPro\Hooks\Keywords;

use Statamic\SeoPro\Linking\Keywords\StopWordsBag;
use Statamic\Support\Traits\Hookable;

class StopWordsHook
{
    use Hookable;

    public function __construct(
        private StopWordsBag $stopWords,
    ) {}

    public function getStopWords(): array
    {
        $payload = $this->runHooksWith('loading', [
            'stopWords' => $this->stopWords,
        ]);

        return $payload->stopWords->toArray();
    }
}
