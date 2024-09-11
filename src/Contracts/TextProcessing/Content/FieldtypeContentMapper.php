<?php

namespace Statamic\SeoPro\Contracts\TextProcessing\Content;

use Statamic\Contracts\Entries\Entry;
use Statamic\Fields\Field;
use Statamic\SeoPro\TextProcessing\Content\ContentMapper;

interface FieldtypeContentMapper
{
    public function withMapper(ContentMapper $mapper): static;

    public static function fieldtype(): string;

    public function withEntry(?Entry $entry): static;

    public function withValue(mixed $value): static;

    public function withFieldConfig(array $fieldConfig): static;

    public function getContent(): void;

    public function getNestedFields(): array;
}