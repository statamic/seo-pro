<?php

namespace Statamic\SeoPro\Content\Mappers;

use Illuminate\Support\Arr;
use Statamic\Fieldtypes\Grid;

class GridFieldMapper extends AbstractFieldMapper
{
    public static function fieldtype(): string
    {
        return Grid::handle();
    }

    public function getContent(): void
    {
        $fields = collect($this->fieldConfig['fields'] ?? [])
            ->keyBy('handle')
            ->all();

        foreach ($this->getValues() as $index => $values) {
            if (count($values) === 0) {
                continue;
            }

            $this->mapper->pushIndex($index);

            foreach ($values as $handle => $value) {
                if (! array_key_exists($handle, $fields)) {
                    continue;
                }

                $fieldType = $fields[$handle]['field']['type'] ?? null;

                if (! $this->mapper->hasMapper($fieldType)) {
                    continue;
                }

                $this->mapper
                    ->append($handle)
                    ->appendDisplayName(Arr::get($fields[$handle], 'field.display'))
                    ->getFieldtypeMapper($fieldType)
                    ->withFieldConfig($fields[$handle]['field'])
                    ->withValue($value)
                    ->getContent();

                $this->mapper->dropNestingLevel();
            }

            $this->mapper->popIndex();
        }
    }
}
