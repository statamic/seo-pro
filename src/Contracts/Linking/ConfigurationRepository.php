<?php

namespace Statamic\SeoPro\Contracts\Linking;

use Illuminate\Support\Collection;
use Statamic\SeoPro\Linking\Config\CollectionConfig;
use Statamic\SeoPro\Linking\Config\SiteConfig;

interface ConfigurationRepository
{
    public function getDisabledCollections(): array;

    public function getCollections(): Collection;

    public function getCollectionConfiguration(string $handle): ?CollectionConfig;

    public function updateCollectionConfiguration(string $handle, CollectionConfig $config): void;

    public function getSites(): Collection;

    public function getSiteConfiguration(string $handle): ?SiteConfig;

    public function updateSiteConfiguration(string $handle, SiteConfig $config): void;

    public function deleteSiteConfiguration(string $handle): void;

    public function deleteCollectionConfiguration(string $handle): void;

    public function resetSiteConfiguration(string $handle): void;

    public function resetCollectionConfiguration(string $handle): void;
}
