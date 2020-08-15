<?php

namespace Statamic\SeoPro\Fieldtypes;

use Statamic\Facades\Blueprint;
use Statamic\Fields\Fields as BlueprintFields;
use Statamic\Fields\Fieldtype;
use Statamic\SeoPro\Fields;
use Statamic\SeoPro\Fields as SeoProFields;
use Statamic\Support\Arr;

class SeoProFieldtype extends Fieldtype
{
    protected $selectable = true;
    protected $icon = 'seo-search-graph';

    public function preProcess($data)
    {
        if ($data === false) {
            $data = ['enabled' => false];
        }

        return $this->fields()->addValues($data ?? [])->preProcess()->values()->all();
    }

    public function preload()
    {
        return [
            'fields' => $this->fieldConfig(),
            'meta' => $this->fields()->meta(),
        ];
    }

    public function process($data)
    {
        if (! Arr::get($data, 'enabled')) {
            return false;
        }

        $values = Arr::removeNullValues(
            $this->fields()->addValues($data)->process()->values()->all()
        );

        return Arr::except($values, 'enabled');
    }

    protected function fields()
    {
        return new BlueprintFields($this->fieldConfig());
    }

    protected function fieldConfig()
    {
        return SeoProFields::new($this->field()->parent())->getConfig();
    }

    public function augment($data)
    {
        if (! is_iterable($data)) {
            return $data;
        }

        return Blueprint::make()
            ->setContents(['fields' => $this->fieldConfig()])
            ->fields()
            ->addValues($data)
            ->augment()
            ->values()
            ->only(array_keys($data))
            ->all();
    }
}
