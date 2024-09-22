<?php

namespace Statamic\SeoPro\Content\Mappers;

use Statamic\Fieldtypes\Text;
use Statamic\SeoPro\Contracts\Content\FieldtypeContentMapper;

class TextFieldMapper extends AbstractFieldMapper implements FieldtypeContentMapper
{
    public static function fieldtype(): string
    {
        return Text::handle();
    }

    public function getContent(): void
    {
        $this->mapper->finish($this->value ?? '');
    }
}
