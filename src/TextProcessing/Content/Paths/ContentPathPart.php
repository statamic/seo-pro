<?php

namespace Statamic\SeoPro\TextProcessing\Content\Paths;

class ContentPathPart
{
    protected array $attributes = [];

    public function __construct(
        public string $name,
        public string $type,
        public array $metaData,
    ) {
        foreach ($this->metaData as $metaDatum) {
            $item = mb_substr($metaDatum, 1, -1);
            [$key, $val] = explode(':', $item, 2);

            $this->attributes[$key] = $val;
        }
    }

    public function isIndex(): bool
    {
        return $this->type === 'index';
    }

    public function isSet(): bool
    {
        return array_key_exists('set', $this->attributes);
    }

    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function __toString(): string
    {
        if ($this->type === 'index') {
            return $this->name;
        }

        if (array_key_exists('set', $this->attributes)) {
            return 'attrs.values.'.$this->name;
        }

        return $this->name;
    }
}
