<?php

namespace Statamic\SeoPro\Contracts\TextProcessing;

use Illuminate\Support\Collection;
use Statamic\SeoPro\TextProcessing\Config\CollectionConfig;
use Statamic\SeoPro\TextProcessing\Config\SiteConfig;

interface ConfigurationRepository
{
    public function getDisabledCollections(): array;

    public function getCollections(): Collection;

    public function getCollectionConfiguration(string $handle): ?CollectionConfig;

    public function updateCollectionConfiguration(string $handle, CollectionConfig $config);

    public function getSites(): Collection;

    public function getSiteConfiguration(string $handle): ?SiteConfig;

    public function updateSiteConfiguration(string $handle, SiteConfig $config);

    public function deleteSiteConfiguration(string $handle);

    public function deleteCollectionConfiguration(string $handle);

    public function resetSiteConfiguration(string $handle);

    public function resetCollectionConfiguration(string $handle);
}