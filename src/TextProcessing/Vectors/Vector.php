<?php

namespace Statamic\SeoPro\TextProcessing\Vectors;

use Statamic\Contracts\Entries\Entry;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class Vector
{
    use FluentlyGetsAndSets;

    protected string $id = '';

    protected ?Entry $entry = null;

    protected array $vector = [];

    public function entry(?Entry $entry = null)
    {
        return $this->fluentlyGetOrSet('entry')
            ->args(func_get_args());
    }

    public function id(?string $id = null)
    {
        return $this->fluentlyGetOrSet('id')
            ->args(func_get_args());
    }

    public function vector(?array $vector = null)
    {
        return $this->fluentlyGetOrSet('vector')
            ->args(func_get_args());
    }
}
