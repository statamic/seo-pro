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

    protected function getValues(): array
    {
        if (! is_array($this->value)) {
            return [];
        }

        return $this->value;
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

            $setFields = collect($set['fields'])->keyBy('handle')->all();
            $values = collect($values)->except(['id', 'type', 'enabled'])->all();

            foreach ($values as $fieldName => $fieldValue) {
                if (! array_key_exists($fieldName, $setFields)) {
                    continue;
                }

                $field = $setFields[$fieldName];
                $type = $field['field']['type'] ?? null;

                if (! $type) {
                    continue;
                }

                $this->mapper
                    ->append($fieldName)
                    ->getFieldtypeMapper($type)
                    ->withFieldConfig($field['field'])
                    ->withValue($fieldValue)
                    ->getContent();

                $this->mapper->dropNestingLevel();
            }
            $this->mapper->popIndex();

        }
    }
}
