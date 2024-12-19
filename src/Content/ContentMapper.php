<?php

namespace Statamic\SeoPro\Content;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Fields\Field;
use Statamic\SeoPro\Content\Paths\ContentPathParser;
use Statamic\Support\Str;

class ContentMapper
{
    protected array $fieldtypeMappers = [];

    protected array $contentMapping = [];

    protected array $values = [];

    protected array $path = [];

    protected array $pushedIndexStack = [];

    protected function isValidMapper(string $mapper): bool
    {
        return class_exists($mapper) && class_implements($mapper, \Statamic\SeoPro\Contracts\Content\FieldtypeContentMapper::class);
    }

    public function registerMapper(string $mapper): static
    {
        if (! $this->isValidMapper($mapper)) {
            return $this;
        }

        $this->fieldtypeMappers[$mapper::fieldtype()] = $mapper;

        return $this;
    }

    public function registerMappers(array $mappers): static
    {
        foreach ($mappers as $mapper) {
            $this->registerMapper($mapper);
        }

        return $this;
    }

    public function startFieldPath(string $handle): static
    {
        $this->path = [$handle];

        return $this;
    }

    public function append(string $value): static
    {
        $this->path[] = $value;

        return $this;
    }

    public function escapeMetaValue(string $value): string
    {
        return Str::swap([
            '\\' => '\\\\',
            '{' => '\\{',
            '}' => '\\}',
            '[' => '\\[',
            ']' => '\\]',
        ], $value);
    }

    public function appendMeta(string $name, string $value): static
    {
        return $this->append('{'.$name.':'.$this->escapeMetaValue($value).'}');
    }

    public function appendDisplayName(?string $display): static
    {
        if (! $display) {
            return $this;
        }

        return $this->appendMeta('display_name', $display);
    }

    public function appendFieldType(string $type): static
    {
        return $this->appendMeta('type', $type);
    }

    public function appendSetName(string $set): static
    {
        return $this->appendMeta('set', $set);
    }

    public function appendNode(string $name): static
    {
        return $this->appendMeta('node', $name);
    }

    public function pushIndex(string $index): static
    {
        $this->path[] = "[{$index}]";
        $this->pushedIndexStack[] = $this->path;

        return $this;
    }

    public function hasMapper(string $fieldType): bool
    {
        return array_key_exists($fieldType, $this->fieldtypeMappers);
    }

    public function getFieldtypeMapper(string $fieldType): \Statamic\SeoPro\Contracts\Content\FieldtypeContentMapper
    {
        /** @var \Statamic\SeoPro\Contracts\Content\FieldtypeContentMapper $fieldtypeMapper */
        $fieldtypeMapper = app($this->fieldtypeMappers[$fieldType]);

        $this->appendFieldType($fieldType);

        return $fieldtypeMapper->withMapper($this);
    }

    protected function indexValues(Entry $entry): void
    {
        $this->values = $entry->toDeferredAugmentedArray();
    }

    public function dropNestingLevel(): static
    {
        $stackCount = count($this->pushedIndexStack);

        if ($stackCount === 0) {
            return $this;
        }

        $this->path = $this->pushedIndexStack[$stackCount - 1];

        return $this;
    }

    public function popIndex(): static
    {
        $toRestore = array_pop($this->pushedIndexStack);

        if (! $toRestore) {
            return $this;
        }

        array_pop($toRestore);

        $this->path = $toRestore;

        return $this;
    }

    public function getPath(): string
    {
        return implode('', $this->path);
    }

    public function addMapping(string $path, string $value): static
    {
        $this->contentMapping[$path] = $value;

        return $this;
    }

    public function finish(string $value): static
    {
        $valuePath = implode('', $this->path);

        if (count($this->pushedIndexStack) > 0) {
            $this->contentMapping[$valuePath] = $value;

            return $this;
        }

        $this->contentMapping[$valuePath] = $value;
        $this->path = [];

        return $this;
    }

    public function newMapper(): ContentMapper
    {
        return tap(new ContentMapper)->registerMappers($this->fieldtypeMappers);
    }

    public function reset(): static
    {
        $this->contentMapping = [];
        $this->values = [];
        $this->path = [];
        $this->pushedIndexStack = [];

        return $this;
    }

    public function getContentMappingFromArray(array $fields, array $values): array
    {
        $this->reset();

        $this->values = $values;

        foreach ($fields as $handle => $field) {
            if (! Arr::get($field, 'field.type')) {
                continue;
            }

            $field = $field['field'];
            $type = $field['type'];

            if (! $this->hasMapper($type)) {
                continue;
            }

            $this->startFieldPath($handle)
                ->appendDisplayName(Arr::get($field, 'field.display'))
                ->getFieldtypeMapper($type)
                ->withFieldConfig($field)
                ->withValue($this->values[$handle] ?? null)
                ->getContent();
        }

        return $this->contentMapping;
    }

    public function getContentMapping(Entry $entry): array
    {
        $this->reset()->indexValues($entry);

        /** @var Field $field */
        foreach ($entry->blueprint()->fields()->all() as $field) {
            $type = $field->type();
            $config = $field->config();

            if (Arr::get($config, 'seo_pro_map_content') === false) {
                continue;
            }

            if (! $this->hasMapper($type)) {
                continue;
            }

            $this->startFieldPath($field->handle())
                ->appendDisplayName($field->display())
                ->getFieldtypeMapper($type)
                ->withEntry($entry)
                ->withFieldConfig($field->config())
                ->withValue($this->values[$field->handle()]?->raw() ?? null)
                ->getContent();
        }

        return $this->contentMapping;
    }

    public function getContentFields(Entry $entry): Collection
    {
        return collect($this->getContentMapping($entry))
            ->map(fn ($_, $path) => $this->retrieveField($entry, $path));
    }

    public function getFieldNames(string $path): array
    {
        return (new ContentPathParser)->parse($path)->getDisplayNames();
    }

    public function retrieveField(Entry $entry, string $path): RetrievedField
    {
        $parsedPath = (new ContentPathParser)->parse($path);
        $dotPath = (string) $parsedPath;

        $rootData = $entry->get($parsedPath->root->name);
        $data = $rootData;

        if (mb_strlen(trim($dotPath)) > 0) {
            $data = Arr::get($rootData, $dotPath);
        }

        return new RetrievedField(
            $data,
            $entry,
            $parsedPath->root->name,
            $path,
            $dotPath,
            $parsedPath->getLastType(),
        );
    }
}
