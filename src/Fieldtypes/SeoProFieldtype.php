<?php

namespace Statamic\Addons\SeoPro\Fieldtypes;

use Statamic\Extend\Fieldtype;
use Statamic\CP\FieldtypeFactory;

class SeoProFieldtype extends Fieldtype
{
    public $selectable = false;

    public function preProcess($data)
    {
        if ($data === false) {
            $data = ['enabled' => false];
        }

        return collect($this->getFieldConfig('fields'))->map(function ($item) {
            return null; // Make sure every field has at least a null value.
        })->merge($data)->map(function ($value, $key) {
            $config = $this->getFieldConfig('fields.'.$key);
            $fieldtype = FieldtypeFactory::create(array_get($config, 'type'), $config);
            return $fieldtype->preProcess($value);
        })->all();
    }

    public function process($data)
    {
        if (! $enabled = array_pull($data, 'enabled')) {
            return false;
        }

        return collect($data)->map(function ($value, $key) {
            $config = $this->getFieldConfig('fields.'.$key);
            $fieldtype = FieldtypeFactory::create(array_get($config, 'type'), $config);
            return $fieldtype->process($value);
        })->reject(function ($item) {
            // Remove empty strings, empty arrays, and nulls. Leave literal false values alone.
            return in_array($item, ['', [], null], true);
        })->all();
    }
}
