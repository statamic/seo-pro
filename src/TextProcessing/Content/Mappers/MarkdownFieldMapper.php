<?php

namespace Statamic\SeoPro\TextProcessing\Content\Mappers;

use Statamic\Fieldtypes\Markdown;

class MarkdownFieldMapper extends TextFieldMapper
{
    public static function fieldtype(): string
    {
        return Markdown::handle();
    }
}