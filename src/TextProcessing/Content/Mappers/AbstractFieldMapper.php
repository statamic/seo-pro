<?php

namespace Statamic\SeoPro\TextProcessing\Content\Mappers;

use Statamic\Contracts\Entries\Entry;
use Statamic\Fields\Field;
use Statamic\SeoPro\Contracts\TextProcessing\Content\FieldtypeContentMapper;
use Statamic\SeoPro\TextProcessing\Content\ContentMapper;

abstract class AbstractFieldMapper implements FieldtypeContentMapper
{
    protected ?Entry $entry = null;

    protected mixed $value = null;

    protected array $fieldConfig = [];

    protected ?ContentMapper $mapper = null;

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