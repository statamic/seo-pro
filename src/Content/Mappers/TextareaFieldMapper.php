<?php

namespace Statamic\SeoPro\Content\Mappers;

use Statamic\Fieldtypes\Textarea;

class TextareaFieldMapper extends TextFieldMapper
{
    public static function fieldtype(): string
    {
        return Textarea::handle();
    }
}
