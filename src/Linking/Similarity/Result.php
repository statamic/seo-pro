<?php

namespace Statamic\SeoPro\Linking\Similarity;

use Statamic\Contracts\Entries\Entry;
use Statamic\SeoPro\Linking\Vectors\Vector;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class Result
{
    use FluentlyGetsAndSets;

    protected int|float $score = 0;

    protected ?Vector $vector = null;

    protected int|float $keywordScore = 0;

    protected ?Entry $entry = null;

    protected array $keywords = [];

    protected array $similarKeywords = [];

    public function score(int|float|null $score = null)
    {
        return $this->fluentlyGetOrSet('score')
            ->args(func_get_args());
    }

    public function vector(?Vector $vector = null)
    {
        return $this->fluentlyGetOrSet('vector')
            ->args(func_get_args());
    }

    public function keywordScore(int|float|null $score = null)
    {
        return $this->fluentlyGetOrSet('keywordScore')
            ->args(func_get_args());
    }

    public function entry(?Entry $entry = null)
    {
        return $this->fluentlyGetOrSet('entry')
            ->args(func_get_args());
    }

    public function keywords(?array $keywords = null)
    {
        return $this->fluentlyGetOrSet('keywords')
            ->args(func_get_args());
    }

    public function similarKeywords(?array $similarKeywords = null)
    {
        return $this->fluentlyGetOrSet('similarKeywords')
            ->args(func_get_args());
    }
}
