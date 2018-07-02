<?php

namespace Statamic\Addons\SeoPro\Fieldtypes;

class SourceFieldFieldtype extends FieldsFieldtype
{
    public function preProcess($config)
    {
        return $this->preProcessField($config);
    }
}
