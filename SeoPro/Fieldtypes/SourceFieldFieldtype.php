<?php

namespace Statamic\Addons\SeoPro\Fieldtypes;

class SourceFieldFieldtype extends FieldsFieldtype
{
    public $selectable = false;

    public function preProcess($config)
    {
        return $config ? $this->preProcessField($config) : $config;
    }
}
