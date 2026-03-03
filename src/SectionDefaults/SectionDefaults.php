<?php

namespace Statamic\SeoPro\SectionDefaults;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Statamic\Facades\Addon;
use Statamic\Facades\Blink;
use Statamic\Facades\Site;

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
    }

    /**
     * Get the origin map, reusing site_defaults_sites from addon settings.
     */
    public static function origins(): Collection
    {
        return Site::all()
            ->mapWithKeys(fn ($site) => [$site->handle() => null])
            ->merge(Addon::get('statamic/seo-pro')->settings()->get('site_defaults_sites', []))
            ->map(fn ($origin) => empty($origin) ? null : $origin);
    }

    private static function rawForHandle(string $type, string $handle): array|false
    {
        // Use settings()->raw() rather than settings()->get() because Statamic's Antlers
        // resolver converts PHP false to an empty string, losing the disabled state.
        $data = Arr::get(Addon::get('statamic/seo-pro')->settings()->raw(), 'section_defaults', []);
        $raw = Arr::get($data, "{$type}.{$handle}");

        if ($raw === false) {
            return false;
        }

        return is_array($raw) ? $raw : [];
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
