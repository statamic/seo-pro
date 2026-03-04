<?php

namespace Statamic\SeoPro\SectionDefaults;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Statamic\Facades\Addon;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy as TaxonomyFacade;

class SectionDefaults
{
    /**
     * Get all LocalizedSectionDefaults for a section across all sites.
     */
    public static function get(string $type, string $handle): Collection
    {
        return Blink::once(self::cacheKey($type, $handle), function () use ($type, $handle) {
            $raw = self::rawForHandle($type, $handle);

            if ($raw === false) {
                return Site::all()->map(fn ($site) => new LocalizedSectionDefaults(
                    type: $type,
                    handle: $handle,
                    locale: $site->handle(),
                    defaults: collect(),
                    enabled: false,
                ));
            }

            return Site::all()->map(function ($site) use ($type, $handle, $raw) {
                $values = Site::multiEnabled()
                    ? Arr::get($raw, "sites.{$site->handle()}", [])
                    : ($raw ?? []);

                return new LocalizedSectionDefaults(
                    type: $type,
                    handle: $handle,
                    locale: $site->handle(),
                    defaults: collect($values),
                    enabled: true,
                );
            });
        });
    }

    /**
     * Get LocalizedSectionDefaults for a specific locale.
     */
    public static function in(string $type, string $handle, string $locale): ?LocalizedSectionDefaults
    {
        return self::get($type, $handle)->get($locale);
    }

    /**
     * Check if a section is globally enabled.
     */
    public static function isEnabled(string $type, string $handle): bool
    {
        return self::rawForHandle($type, $handle) !== false;
    }

    /**
     * Disable a section globally (for all sites).
     */
    public static function disable(string $type, string $handle): void
    {
        $data = Addon::get('statamic/seo-pro')->settings()->get('section_defaults', []);

        Arr::set($data, "{$type}.{$handle}", false);

        Addon::get('statamic/seo-pro')->settings()->set('section_defaults', $data)->save();

        self::clearCache($type, $handle);
        self::clearInjectSeo($type, $handle);
    }

    /**
     * Save a LocalizedSectionDefaults instance to addon settings.
     */
    public static function save(LocalizedSectionDefaults $localized): void
    {
        $data = Addon::get('statamic/seo-pro')->settings()->get('section_defaults', []);
        $type = $localized->type();
        $handle = $localized->handle();
        $locale = $localized->locale();
        $values = $localized->all();

        if (Site::multiEnabled()) {
            // Clear any top-level false (disabled) value before saving site data.
            if (Arr::get($data, "{$type}.{$handle}") === false) {
                Arr::forget($data, "{$type}.{$handle}");
            }

            if (empty($values)) {
                Arr::forget($data, "{$type}.{$handle}.sites.{$locale}");

                // If no sites have values left, remove the entire handle entry.
                $remainingSites = Arr::get($data, "{$type}.{$handle}.sites", []);
                if (empty($remainingSites)) {
                    Arr::forget($data, "{$type}.{$handle}");
                }
            } else {
                Arr::set($data, "{$type}.{$handle}.sites.{$locale}", $values);
            }
        } else {
            if (empty($values)) {
                Arr::forget($data, "{$type}.{$handle}");
            } else {
                Arr::set($data, "{$type}.{$handle}", $values);
            }
        }

        Addon::get('statamic/seo-pro')->settings()->set('section_defaults', $data)->save();

        self::clearCache($type, $handle);
        self::clearInjectSeo($type, $handle);
    }

    /**
     * Get or set the per-site origin map for section defaults.
     */
    public static function origins($origins = null): Collection|bool
    {
        if (func_num_args() === 0) {
            return Site::all()
                ->mapWithKeys(fn ($site) => [$site->handle() => null])
                ->merge(Addon::get('statamic/seo-pro')->settings()->get('section_defaults_sites', []))
                ->map(fn ($origin) => empty($origin) ? null : $origin);
        }

        Addon::get('statamic/seo-pro')->settings()->set('section_defaults_sites', $origins)->save();

        return true;
    }

    private static function rawForHandle(string $type, string $handle): array|false
    {
        // Use settings()->raw() rather than settings()->get() because Statamic's Antlers
        // resolver converts PHP false to an empty string, losing the disabled state.
        $data = Arr::get(Addon::get('statamic/seo-pro')->settings()->raw(), 'section_defaults', []);

        // If explicitly present in addon settings, use that value.
        if (Arr::has($data, "{$type}.{$handle}")) {
            $raw = Arr::get($data, "{$type}.{$handle}");

            if ($raw === false) {
                return false;
            }

            return is_array($raw) ? $raw : [];
        }

        // Fall back to inject.seo from the collection/taxonomy YAML (legacy storage).
        return self::rawFromInjectSeo($type, $handle);
    }

    /**
     * Read section defaults from the legacy inject.seo location in collection/taxonomy YAML.
     */
    private static function rawFromInjectSeo(string $type, string $handle): array|false
    {
        $item = self::findSectionItem($type, $handle);

        if (! $item) {
            return [];
        }

        $injectSeo = Arr::get($item->fileData(), 'inject.seo');

        if ($injectSeo === null) {
            return [];
        }

        if ($injectSeo === false) {
            return false;
        }

        if (! is_array($injectSeo) || empty($injectSeo)) {
            return [];
        }

        // inject.seo was always a flat set of values (no per-site support in the old design).
        // In multi-site mode, treat these as the default site's values so origin inheritance
        // can propagate them to child sites naturally.
        if (Site::multiEnabled()) {
            return ['sites' => [Site::default()->handle() => $injectSeo]];
        }

        return $injectSeo;
    }

    /**
     * Strip inject.seo from the collection/taxonomy YAML, migrating it to addon settings.
     * Called automatically after saving or disabling via the new API.
     */
    private static function clearInjectSeo(string $type, string $handle): void
    {
        $item = self::findSectionItem($type, $handle);

        if (! $item) {
            return;
        }

        if (! Arr::has($item->fileData(), 'inject.seo')) {
            return;
        }

        $cascade = $item->cascade();
        $cascade->forget('seo');
        $item->cascade($cascade->all())->save();
    }

    private static function findSectionItem(string $type, string $handle)
    {
        return $type === 'collections'
            ? CollectionFacade::find($handle)
            : TaxonomyFacade::find($handle);
    }

    private static function cacheKey(string $type, string $handle): string
    {
        return "seo-pro::section-defaults::{$type}::{$handle}";
    }

    public static function clearCache(string $type, string $handle): void
    {
        Blink::forget(self::cacheKey($type, $handle));
    }
}
