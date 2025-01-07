<?php

namespace Statamic\SeoPro\Content\Mappers;

use Statamic\Fieldtypes\Group;

class GroupFieldMapper extends AbstractFieldMapper
{
    public static function fieldtype(): string
    {
        return Group::handle();
    }

    public function getContent(): void
    {
        $this->mapper->append('[.]');

        $this->mapNestedFields(
            $this->getValues(),
            collect($this->fieldConfig['fields'] ?? [])->keyBy('handle')->all()
        );
    }
}
