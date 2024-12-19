<?php

namespace Statamic\SeoPro\Content\Paths;

class ContentPath
{
    public function __construct(
        public array $parts,
        public ContentPathPart $root,
    ) {}

    public function getLastType(): ?string
    {
        $type = null;

        $lastType = $this->root->getAttribute('type');

        /** @var ContentPathPart $part */
        foreach ($this->parts as $part) {
            if ($type = $part->getAttribute('type')) {
                $lastType = $type;
            }
        }

        return $lastType;
    }

    public function getDisplayNames(): array
    {
        return collect($this->parts)
            ->map(fn (ContentPathPart $part) => $part->displayName())
            ->prepend($this->root->displayName())
            ->filter()
            ->all();
    }

    public function __toString(): string
    {
        return str(
            collect($this->parts)
                ->map(fn ($part) => (string) $part)
                ->implode('.')
        )
            ->trim('.')
            ->value();
    }
}
