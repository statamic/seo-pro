<?php

namespace Statamic\SeoPro\TextProcessing\Content\Mappers;

use Statamic\Fieldtypes\Textarea;

class TextareaFieldMapper extends TextFieldMapper
{
    public static function fieldtype(): string
    {
        return Textarea::handle();
    }
}
