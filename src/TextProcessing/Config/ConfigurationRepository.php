<?php

namespace Statamic\SeoPro\TextProcessing\Config;

use Illuminate\Support\Collection;
use Statamic\Facades\Collection as CollectionApi;
use Statamic\Facades\Site as SiteApi;
use Statamic\SeoPro\Contracts\TextProcessing\ConfigurationRepository as ConfigurationRepositoryContract;
use Statamic\SeoPro\TextProcessing\Models\CollectionLinkSettings;
use Statamic\SeoPro\TextProcessing\Models\SiteLinkSetting;

class ConfigurationRepository implements ConfigurationRepositoryContract
{
    public function getCollections(): Collection
    {
        $collections = [];
        $allCollections = CollectionApi::all();
        $settings = CollectionLinkSettings::all()->keyBy('collection')->all();

        foreach ($allCollections as $collection) {
            $handle = $collection->handle();
            $title = $collection->title();

            if (array_key_exists($handle, $settings)) {
                $collections[] = $this->makeCollectionConfig($handle, $title, $settings[$handle]);
            } else {
                $collections[] = $this->makeDefaultCollectionConfig($handle, $title);
            }
        }

        return collect($collections);
    }

    protected function makeDefaultCollectionConfig(string $handle, string $title): CollectionConfig
    {
        return new CollectionConfig(
            $handle,
            $title,
            true,
            false,
            true,
            [],
        );
    }

    protected function makeCollectionConfig(string $handle, string $title, CollectionLinkSettings $settings): CollectionConfig
    {
        return new CollectionConfig(
            $handle,
            $title,
            $settings->linking_enabled,
            $settings->allow_linking_across_sites,
            $settings->allow_linking_to_all_collections,
            $settings->linkable_collections ?? [],
        );
    }

    public function getCollectionConfiguration(string $handle): ?CollectionConfig
    {
        $config = CollectionLinkSettings::where('collection', $handle)->first();

        if ($config) {
            // TODO: Hydrate title.

            return $this->makeCollectionConfig($handle, '', $config);
        }

        return $this->makeDefaultCollectionConfig($handle, '');
    }

    public function updateCollectionConfiguration(string $handle, CollectionConfig $config)
    {
        /** @var CollectionLinkSettings $collectionSettings */
        $collectionSettings = CollectionLinkSettings::firstOrNew(['collection' => $handle]);

        $collectionSettings->linkable_collections = $config->linkableCollections;
        $collectionSettings->allow_linking_to_all_collections = $config->allowLinkingToAllCollections;
        $collectionSettings->allow_linking_across_sites = $config->allowLinkingAcrossSites;
        $collectionSettings->linking_enabled = $config->linkingEnabled;

        $collectionSettings->saveQuietly();
    }

    public function getSites(): Collection
    {
        $sites = [];
        $allSites = SiteApi::all();
        $settings = SiteLinkSetting::all()->keyBy('site')->all();

        foreach ($allSites as $site) {
            $handle = $site->handle();
            $name = $site->name();

            if (array_key_exists($handle, $settings)) {
                $sites[] = $this->makeSiteConfig($handle, $name, $settings[$handle]);
            } else {
                $sites[] = $this->makeDefaultSiteConfig($handle, $name);
            }
        }

        return collect($sites);
    }

    protected function makeSiteConfig(string $handle, string $name, SiteLinkSetting $config): SiteConfig
    {
        return new SiteConfig(
            $handle,
            $name,
            $config->ignored_phrases,
            $config->keyword_threshold * 100,
            $config->min_internal_links,
            $config->max_internal_links,
            $config->min_external_links,
            $config->max_external_links,
            $config->prevent_circular_links,
        );
    }

    protected function makeDefaultSiteConfig(string $handle, string $name): SiteConfig
    {
        return new SiteConfig(
            $handle,
            $name,
            [],
            config('statamic.seo-pro.text_analysis.keyword_threshold', 65),
            config('statamic.seo-pro.text_analysis.internal_links.min_desired', 3),
            config('statamic.seo-pro.text_analysis.internal_links.max_desired', 6),
            config('statamic.seo-pro.text_analysis.external_links.min_desired', 0),
            config('statamic.seo-pro.text_analysis.external_links.max_desired', 0),
            config('statamic.seo-pro.text_analysis.prevent_circular_links', false)
        );
    }

    public function getSiteConfiguration(string $handle): ?SiteConfig
    {
        $config = SiteLinkSetting::where('site', $handle)->first();

        if ($config) {
            // TODO: Hydrate name.

            return $this->makeSiteConfig($handle, '', $config);
        }

        return $this->makeDefaultSiteConfig($handle, '');
    }

    public function updateSiteConfiguration(string $handle, SiteConfig $config)
    {
        /** @var SiteLinkSetting $siteSettings */
        $siteSettings = SiteLinkSetting::firstOrNew(['site' => $handle]);

        $siteSettings->ignored_phrases = $config->ignoredPhrases;
        $siteSettings->keyword_threshold = $config->keywordThreshold / 100;
        $siteSettings->min_internal_links = $config->minInternalLinks;
        $siteSettings->max_internal_links = $config->maxInternalLinks;
        $siteSettings->min_external_links = $config->minExternalLinks;
        $siteSettings->max_external_links = $config->maxExternalLinks;
        $siteSettings->prevent_circular_links = $config->preventCircularLinks;

        $siteSettings->saveQuietly();
    }

    public function getDisabledCollections(): array
    {
        $disabled = CollectionLinkSettings::where('linking_enabled', false)
            ->select('collection')
            ->get()
            ->pluck('collection')
            ->all();

        return array_merge(
            config('statamic.seo-pro.text_analysis.disabled_collections', []),
            $disabled
        );
    }

    public function deleteSiteConfiguration(string $handle)
    {
        SiteLinkSetting::where('site', $handle)->delete();
    }

    public function deleteCollectionConfiguration(string $handle)
    {
        CollectionLinkSettings::where('collection', $handle)->delete();
    }

    public function resetSiteConfiguration(string $handle)
    {
        /** @var SiteLinkSetting $settings */
        $settings = SiteLinkSetting::where('site', $handle)->first();

        if (! $settings) {
            return;
        }

        $settings->keyword_threshold = config('statamic.seo-pro.text_analysis.keyword_threshold', 65) / 100;
        $settings->min_internal_links = config('statamic.seo-pro.text_analysis.internal_links.min_desired', 3);
        $settings->max_internal_links = config('statamic.seo-pro.text_analysis.internal_links.max_desired', 6);
        $settings->min_external_links = config('statamic.seo-pro.text_analysis.external_links.min_desired', 0);
        $settings->max_external_links = config('statamic.seo-pro.text_analysis.external_links.max_desired', 0);
        $settings->prevent_circular_links = config('statamic.seo-pro.text_analysis.prevent_circular_links', false);

        $settings->ignored_phrases = [];

        $settings->save();
    }

    public function resetCollectionConfiguration(string $handle)
    {
        /** @var CollectionLinkSettings $settings */
        $settings = CollectionLinkSettings::where('collection', $handle)->first();

        if (! $settings) {
            return;
        }

        $settings->allow_linking_to_all_collections = true;
        $settings->linking_enabled = true;
        $settings->allow_linking_across_sites = false;
        $settings->linkable_collections = [];

        $settings->save();
    }
}
