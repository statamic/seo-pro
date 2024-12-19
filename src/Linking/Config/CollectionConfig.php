<?php

namespace Statamic\SeoPro\Linking\Config;

class CollectionConfig
{
    public function __construct(
        public string $handle,
        public string $title,
        public bool $linkingEnabled,
        public bool $allowLinkingAcrossSites,
        public bool $allowLinkingToAllCollections,
        public array $linkableCollections,
    ) {}

    public function toArray(): array
    {
        return [
            'allow_cross_collection_suggestions' => $this->allowLinkingToAllCollections,
            'allow_cross_site_linking' => $this->allowLinkingAcrossSites,
            'allowed_collections' => $this->linkableCollections,
            'linking_enabled' => $this->linkingEnabled,
            'collection_handle' => $this->handle,
            'handle' => $this->handle,
            'title' => $this->title,
        ];
    }

    public function __get(string $name)
    {
        return $this->toArray()[$name] ?? null;
    }
}
