<?php

namespace Statamic\SeoPro\TextProcessing\Content;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Fields\Field;
use Statamic\SeoPro\Contracts\TextProcessing\Content\FieldtypeContentMapper;
use Statamic\SeoPro\TextProcessing\Content\Paths\ContentPath;
use Statamic\SeoPro\TextProcessing\Content\Paths\ContentPathParser;
use Statamic\SeoPro\TextProcessing\Content\Paths\ContentPathPart;

class ContentMapper
{
    protected array $fieldtypeMappers = [];

    protected array $contentMapping = [];

    protected array $values = [];

    protected array $path = [];

    protected array $pushedIndexStack = [];

    protected function isValidMapper(string $mapper): bool
    {
        return class_exists($mapper) && class_implements($mapper, FieldtypeContentMapper::class);
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

    public function appendMeta(string $name, string $value): static
    {
        return $this->append('{'.$name.':'.$value.'}');
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

    public function getFieldtypeMapper(string $fieldType): FieldtypeContentMapper
    {
        /** @var FieldtypeContentMapper $fieldtypeMapper */
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
                ->getFieldtypeMapper($type)
                ->withFieldConfig($field)
                ->withValue($this->values[$handle] ?? null)
                ->getContent();
        }

        return $this->contentMapping;
    }

    public function getFieldConfigForEntry(Entry $entry, string $path): ?RetrievedConfig
    {
        $parsedPath = (new ContentPathParser)->parse($path);
        $fields = $entry->blueprint()->fields()->all();
        $field = $fields[$parsedPath->root->name] ?? null;

        if (! $field) {
            return null;
        }

        return $this->getFieldConfig($field, $parsedPath);
    }

    public function getFieldConfig(Field $field, ContentPath $path): RetrievedConfig
    {
        $config = $field->config();
        $names = [];

        // TODO: This can get refactored/cleaned up a bit.
        // TODO: Groups/how to handle third-party fieldtypes/etc.

        if (array_key_exists('display', $config)) {
            $names[] = $config['display'];
        }

        /** @var ContentPathPart $part */
        foreach ($path->parts as $part) {
            if ($part->isIndex()) {
                continue;
            }

            if ($part->isSet()) {
                if (! array_key_exists('sets', $config)) {
                    break;
                }

                $sets = $config['sets'];
                $set = $part->getAttribute('set');

                foreach ($sets as $group) {
                    foreach ($group['sets'] as $setName => $setConfig) {
                        if ($setName != $set) {
                            continue;
                        }

                        $matchingField = collect($setConfig['fields'])->where('handle', $part->name)->first();

                        if (! $matchingField) {
                            $config = null;
                            break 3;
                        }

                        $config = $matchingField['field'];

                        if (array_key_exists('display', $config)) {
                            $names[] = $config['display'];
                        }

                        break 2;
                    }
                }

                continue;
            }

            if (array_key_exists('fields', $config)) {
                $matchingField = collect($config['fields'])->where('handle', $part->name)->first();

                if (! $matchingField) {
                    $config = null;
                    break;
                }

                $config = $matchingField['field'];

                if (array_key_exists('display', $config)) {
                    $names[] = $config['display'];
                }

                continue;
            }
        }

        return new RetrievedConfig(
            $config,
            $names
        );
    }

    public function getContentMapping(Entry $entry): array
    {
        $this->reset();

        $this->indexValues($entry);

        $fields = $entry->blueprint()->fields()->all();

        /** @var Field $field */
        foreach ($fields as $field) {
            $type = $field->type();

            if (! $this->hasMapper($type)) {
                continue;
            }

            $this->startFieldPath($field->handle());

            $this->getFieldtypeMapper($type)
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
