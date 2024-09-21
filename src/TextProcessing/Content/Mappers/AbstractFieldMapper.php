<?php

namespace Statamic\SeoPro\TextProcessing\Content\Mappers;

use Illuminate\Support\Arr;
use Statamic\Contracts\Entries\Entry;
use Statamic\SeoPro\Contracts\TextProcessing\Content\FieldtypeContentMapper;
use Statamic\SeoPro\TextProcessing\Content\ContentMapper;

abstract class AbstractFieldMapper implements FieldtypeContentMapper
{
    protected ?Entry $entry = null;

    protected mixed $value = null;

    protected array $fieldConfig = [];

    protected ?ContentMapper $mapper = null;

    protected function getValues(): array
    {
        if (! is_array($this->value)) {
            return [];
        }

        return $this->value;
    }

    protected function mapNestedFields(array $values, array $fields): void
    {
        foreach ($values as $fieldName => $fieldValue) {
            if (! array_key_exists($fieldName, $fields)) {
                continue;
            }

            $field = $fields[$fieldName];
            $type = $field['field']['type'] ?? null;

            if (! $type) {
                continue;
            }

            $this->mapper
                ->append($fieldName)
                ->appendDisplayName(Arr::get($field, 'field.display'))
                ->getFieldtypeMapper($type)
                ->withFieldConfig($field['field'])
                ->withValue($fieldValue)
                ->getContent();

            $this->mapper->dropNestingLevel();
        }
    }

    public function withMapper(ContentMapper $mapper): static
    {
        $this->mapper = $mapper;

        return $this;
    }

    public function withEntry(?Entry $entry): static
    {
        $this->entry = $entry;

        return $this;
    }

    public function withValue(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function withFieldConfig(array $fieldConfig): static
    {
        $this->fieldConfig = $fieldConfig;

        return $this;
    }

    public function getNestedFields(): array
    {
        return [];
    }
}
