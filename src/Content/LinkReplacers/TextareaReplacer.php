<?php

namespace Statamic\SeoPro\Content\LinkReplacers;

use Statamic\Fieldtypes\Textarea;

class TextareaReplacer extends TextReplacer
{
    public static function fieldtype(): string
    {
        return Textarea::handle();
    }
}
