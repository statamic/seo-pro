<?php

namespace Statamic\SeoPro\TextProcessing\Content\Mappers;

use Statamic\Fieldtypes\Replicator;
use Statamic\SeoPro\TextProcessing\Content\Mappers\Concerns\GetsSets;

class ReplicatorFieldMapper extends AbstractFieldMapper
{
    use GetsSets;

    public static function fieldtype(): string
    {
        return Replicator::handle();
    }

    public function getContent(): void
    {
        $sets = $this->getSets();

        foreach ($this->getValues() as $index => $values) {
            if (count($values) === 0) {
                continue;
            }

            if (! array_key_exists('type', $values)) {
                continue;
            }

            $type = $values['type'];

            if (! array_key_exists($type, $sets)) {
                continue;
            }

            $set = $sets[$type];

            if (! array_key_exists('fields', $set)) {
                continue;
            }

            $this->mapper->pushIndex($index);

            $this->mapNestedFields(
                collect($values)->except(['id', 'type', 'enabled'])->all(),
                collect($set['fields'])->keyBy('handle')->all()
            );

            $this->mapper->popIndex();
        }
    }
}
