<?php

namespace Statamic\SeoPro\Fieldtypes;

use Statamic\Fields\Fieldtype;

class SeoProPreviews extends Fieldtype
{
    public $selectable = false;

    public function preProcess($data)
    {
        return $data;
    }
}