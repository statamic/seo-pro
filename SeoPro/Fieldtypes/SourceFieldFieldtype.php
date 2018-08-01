<?php

namespace Statamic\Addons\SeoPro\Fieldtypes;

class SourceFieldFieldtype extends FieldsFieldtype
{
    public $selectable = false;

    public function preProcess($config)
    {
        return $this->preProcessField($config);
    }
}
