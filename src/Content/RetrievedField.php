<?php

namespace Statamic\SeoPro\Content;

use Illuminate\Support\Arr;
use Statamic\Contracts\Entries\Entry;

class RetrievedField
{
    public function __construct(
        protected mixed $value,
        protected Entry $entry,
        private readonly string $root,
        private readonly string $originalPath,
        private readonly string $path,
        private readonly ?string $lastType
    ) {}

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'root' => $this->root,
            'fqn_path' => $this->originalPath,
            'normalized_path' => $this->path,
            'last_fieldtype' => $this->lastType,
        ];
    }

    public function getLastType(): ?string
    {
        return $this->lastType;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getEntry(): Entry
    {
        return $this->entry;
    }

    public function getRoot(): string
    {
        return $this->root;
    }

    public function getOriginalPath(): string
    {
        return $this->originalPath;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getRootData()
    {
        return $this->entry->get($this->root);
    }

    protected function arrUnset(&$array, $key)
    {
        if (is_null($key)) {
            return;
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (! isset($array[$key]) || ! is_array($array[$key])) {
                return;
            }

            $array = &$array[$key];
        }

        unset($array[array_shift($keys)]);

    }

    protected function setRoot($newData): static
    {
        $this->entry->set($this->root, $newData);

        return $this;
    }

    public function update(mixed $newValue): static
    {
        $data = $this->getRootData();

        if (is_array($data)) {
            if (strlen($this->path) > 0) {
                Arr::set($data, $this->path, $newValue);
            } else {
                $data = $newValue;
            }
        } elseif (is_string($data) && is_string($newValue)) {
            $data = $newValue;
        }

        return $this->setRoot($data);
    }

    public function delete(): static
    {
        $data = $this->getRootData();
        $this->arrUnset($data, $this->path);

        return $this->setRoot($data);
    }

    public function save()
    {
        return $this->entry->save();
    }
}
