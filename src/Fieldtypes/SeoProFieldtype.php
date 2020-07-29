<?php

namespace Statamic\SeoPro\Fieldtypes;

use Statamic\Fields\Fields;
use Statamic\Fields\Fieldtype;
use Statamic\Support\Arr;

class SeoProFieldtype extends Fieldtype
{
    public $selectable = false;

    public function preProcess($data)
    {
        return $this->fields()->addValues($data ?? [])->preProcess()->values()->all();
    }

    public function preload()
    {
        return $this->fields()->meta();
    }

    public function process($data)
    {
        if (! $enabled = Arr::get($data, 'enabled')) {
            return false;
        }

        $values = Arr::removeNullValues(
            $this->fields()->addValues($data)->process()->values()->all()
        );

        return Arr::except($values, 'enabled');
    }

    protected function fields()
    {
        return new Fields($this->config('fields'));
    }
}
