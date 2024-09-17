<?php

namespace Statamic\SeoPro\TextProcessing\Content\Mappers;

use Statamic\Fieldtypes\Grid;

class GridFieldMapper extends AbstractFieldMapper
{
    public static function fieldtype(): string
    {
        return Grid::handle();
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
