<?php

namespace Statamic\SeoPro\Http;

use Illuminate\Contracts\Support\Arrayable;
use Statamic\Fields\Blueprint;

class ValuesResponse implements Arrayable
{
    public function __construct(
        protected Blueprint $blueprint,
        protected array $data,
    ) {}

    public function toArray()
    {
        $fields = $this->blueprint
            ->fields()
            ->addValues($this->data)
            ->preProcess();

        return [
            'values' => $fields->values()->all(),
            'meta' => $fields->meta()->all(),
        ];
    }
}
