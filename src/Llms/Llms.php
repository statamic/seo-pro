<?php

namespace Statamic\SeoPro\Llms;

use Illuminate\Support\Arr;
use RuntimeException;
use Statamic\Contracts\Addons\SettingsRepository;
use Statamic\Facades\Addon;
use Statamic\Facades\Blink;
use Statamic\Facades\Site;
use Statamic\Facades\URL;
use Statamic\Sites\Site as SiteObject;

class Llms
{
    public static function get(string|SiteObject|null $site = null): LlmsDocument
    {
        $site = self::site($site);

        return Blink::once("seo-pro::llms.{$site->handle()}", function () use ($site) {
            $data = Arr::get(
                Addon::get('statamic/seo-pro')->settings()->raw(),
                "llms.sites.{$site->handle()}.policy",
                [],
            );

            return new LlmsDocument(is_array($data) ? $data : []);
        });
    }

    public static function generated(string|SiteObject|null $site = null): ?array
    {
        $site = self::site($site);
        $generated = Arr::get(
            Addon::get('statamic/seo-pro')->settings()->raw(),
            "llms.sites.{$site->handle()}.generated",
        );

        return is_array($generated) ? $generated : null;
    }

    public static function save(LlmsDocument $document, string|SiteObject|null $site = null): bool
    {
        $site = self::site($site);
        $data = ['policy' => $document->all()];

        if ($generated = self::generated($site)) {
            $data['generated'] = $generated;
        }

        return self::saveSiteSettings($site, $data);
    }

    public static function saveGenerated(
        LlmsDocument $document,
        SiteObject $site,
        string $contents,
        string $path,
        \DateTimeInterface $generatedAt,
    ): bool {
        return self::saveSiteSettings($site, [
            'policy' => $document->all(),
            'generated' => [
                'timestamp' => $generatedAt->format(DATE_ATOM),
                'checksum' => hash('sha256', $contents),
                'path' => $path,
            ],
        ]);
    }

    public static function saveWithoutGenerated(LlmsDocument $document, SiteObject $site): bool
    {
        return self::saveSiteSettings($site, ['policy' => $document->all()]);
    }

    public static function withDefaults(array $data): array
    {
        $defaults = self::defaults();
        $values = array_replace($defaults, Arr::only($data, array_keys($defaults)));

        $values['enabled'] = filter_var($values['enabled'], FILTER_VALIDATE_BOOL);
        $values['mode'] = in_array($values['mode'], ['managed', 'custom'], true)
            ? $values['mode']
            : $defaults['mode'];
        $values['title'] = (string) ($values['title'] ?? '');
        $values['summary'] = (string) ($values['summary'] ?? '');
        $values['details'] = (string) ($values['details'] ?? '');
        $values['custom_source'] = (string) ($values['custom_source'] ?? '');
        $values['sections'] = collect(is_array($values['sections']) ? $values['sections'] : [])
            ->filter(fn ($section) => is_array($section))
            ->map(function (array $section) {
                return [
                    'title' => (string) ($section['title'] ?? ''),
                    'links' => collect(is_array($section['links'] ?? null) ? $section['links'] : [])
                        ->filter(fn ($link) => is_array($link))
                        ->map(fn (array $link) => [
                            'title' => (string) ($link['title'] ?? ''),
                            'url' => (string) ($link['url'] ?? ''),
                            'description' => (string) ($link['description'] ?? ''),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();

        return $values;
    }

    public static function defaults(): array
    {
        return [
            'enabled' => false,
            'mode' => 'managed',
            'title' => '{{ config:app:name }}',
            'summary' => '',
            'details' => '',
            'sections' => [],
            'custom_source' => '',
        ];
    }

    public static function enabledSites()
    {
        return Site::all()->filter(fn (SiteObject $site) => self::get($site)->enabled());
    }

    public static function url(string|SiteObject|null $site = null): string
    {
        $site = self::site($site);

        return URL::tidy($site->absoluteUrl().'/llms.txt', external: true, withTrailingSlash: false);
    }

    public static function relativePath(string|SiteObject|null $site = null): string
    {
        $path = parse_url(self::site($site)->absoluteUrl(), PHP_URL_PATH) ?: '/';

        return trim($path, '/') === '' ? 'llms.txt' : trim($path, '/').'/llms.txt';
    }

    public static function settingsSnapshot(): array
    {
        $addon = Addon::get('statamic/seo-pro');
        $settings = app(SettingsRepository::class)->find($addon->id());

        return [
            'exists' => $settings !== null,
            'raw' => $settings?->raw() ?? [],
        ];
    }

    public static function restoreSettings(array $snapshot): void
    {
        $addon = Addon::get('statamic/seo-pro');
        $repository = app(SettingsRepository::class);

        try {
            if ($snapshot['exists']) {
                $repository->save($repository->make($addon, $snapshot['raw']));
            } elseif ($settings = $repository->find($addon->id())) {
                $repository->delete($settings);
            }
        } finally {
            self::forget();
        }

        if (self::settingsSnapshot() !== $snapshot) {
            throw new RuntimeException('Unable to restore the previous SEO Pro settings.');
        }
    }

    public static function forget(string|SiteObject|null $site = null): void
    {
        if ($site) {
            $site = self::site($site);
            Blink::forget("seo-pro::llms.{$site->handle()}");

            return;
        }

        Site::all()->each(fn (SiteObject $site) => Blink::forget("seo-pro::llms.{$site->handle()}"));
    }

    public static function site(string|SiteObject|null $site = null): SiteObject
    {
        if ($site instanceof SiteObject) {
            return $site;
        }

        if (is_string($site)) {
            return Site::get($site) ?? throw new \InvalidArgumentException("Unknown Statamic site [{$site}].");
        }

        return Site::current() ?? Site::default();
    }

    private static function saveSiteSettings(SiteObject $site, array $siteData): bool
    {
        $settings = Addon::get('statamic/seo-pro')->settings();
        $llms = Arr::get($settings->raw(), 'llms', []);
        $llms = is_array($llms) ? $llms : [];
        $sites = is_array($llms['sites'] ?? null) ? $llms['sites'] : [];
        $sites[$site->handle()] = $siteData;
        $llms['sites'] = $sites;

        $saved = $settings->set('llms', $llms)->save();

        if ($saved) {
            self::forget($site);
        }

        return $saved;
    }
}
