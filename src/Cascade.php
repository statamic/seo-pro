<?php

namespace Statamic\SeoPro;

use Illuminate\Support\Collection;
use Statamic\Contracts\Assets\Asset as AssetContract;
use Statamic\Contracts\Query\Builder;
use Statamic\Facades\Antlers;
use Statamic\Facades\Asset;
use Statamic\Facades\Blink;
use Statamic\Facades\Config;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\URL;
use Statamic\Fields\Field;
use Statamic\Fields\Value;
use Statamic\Fieldtypes\Bard;
use Statamic\Statamic;
use Statamic\Support\Arr;
use Statamic\Support\Str;
use Statamic\View\Antlers\Language\Exceptions\RuntimeException;
use Statamic\View\Cascade as ViewCascade;

class Cascade
{
    protected $data;
    protected $siteDefaults;
    protected $sectionDefaults;
    protected $current;
    protected $explicitUrl;
    protected $model;
    protected $forSitemap = false;

    public function __construct()
    {
        $this->data = collect();

        $this->model = new NullModel;
    }

    public function forSitemap()
    {
        $this->forSitemap = true;

        return $this;
    }

    public function with($array)
    {
        $this->data = $this->data->merge($array);

        return $this;
    }

    public function withSiteDefaults($data)
    {
        $this->with($data);

        $this->siteDefaults = $data;

        return $this;
    }

    public function withSectionDefaults($data)
    {
        $this->with($data);

        $this->sectionDefaults = $data;

        return $this;
    }

    public function withCurrent($data)
    {
        if (is_null($data)) {
            return $this;
        }

        $this->current = $this->augmentData($data);

        $this->model = $data ?? new NullModel;

        return $this;
    }

    public function withExplicitUrl($url)
    {
        $this->explicitUrl = $url;

        return $this;
    }

    public function get()
    {
        $this->hydrateCascade();

        if (! $this->current) {
            $this->withCurrent(Entry::findByUri('/'));
            $this->withExplicitUrl(request()->url());
        }

        if ($this->forSitemap) {
            return $this->getForSitemap();
        }

        if (Arr::get($this->data, 'response_code') === 404) {
            $this->current['title'] = '404 Page Not Found';
        }

        $this->data = $this->data->map(function ($item, $key) {
            // Skip json_ld_schema. It might need to use resolved SEO values as context.
            if ($key === 'json_ld_schema') {
                return $item;
            }

            return $this->parse($key, $item);
        });

        if ($this->data->has('json_ld_schema')) {
            $this->data->put('json_ld_schema', $this->parseJsonLdSchema($this->data->get('json_ld_schema')));
        }

        return $this->data->merge([
            'compiled_title' => $this->compiledTitle(),
            'og_type' => $this->data->get('og_type') ?: 'website',
            'og_title' => $this->ogTitle(),
            'canonical_url' => $this->canonicalUrl(),
            'prev_url' => $this->prevUrl(),
            'next_url' => $this->nextUrl(),
            'home_url' => $this->homeUrl(),
            'humans_txt' => $this->humans(),
            'site' => $this->site(),
            'is_default_site' => $this->site()->isDefault(),
            'alternate_locales' => $alternateLocales = $this->alternateLocales(),
            'current_hreflang' => $this->currentHreflang($alternateLocales),
            'last_modified' => $this->lastModified(),
            'twitter_card' => config('statamic.seo-pro.twitter.card'),
            'twitter_title' => $this->twitterTitle(),
            'twitter_description' => $this->twitterDescription(),
            'robots' => $this->robots(),
            'robots_indexing' => $this->robotsIndexing(),
            'json_ld' => $this->jsonLd(),
        ])->all();
    }

    public function getForSitemap()
    {
        $this->data = $this->data
            ->filter(function ($value, $key) {
                return in_array($key, [
                    'canonical_url',
                    'priority',
                    'change_frequency',
                ]);
            })
            ->map(function ($item, $key) {
                return $this->parse($key, $item);
            });

        return $this->data->merge([
            'last_modified' => $this->lastModified(),
        ])->all();
    }

    public function value($key)
    {
        return $this->get()[$key] ?? null;
    }

    public function homeUrl()
    {
        return Site::current()?->absoluteUrl() ?? URL::makeAbsolute('/');
    }

    protected function isHomePage(): bool
    {
        if (! method_exists($this->model, 'absoluteUrl')) {
            return false;
        }

        $home = Str::removeRight((string) $this->site()->absoluteUrl(), '/');
        $current = Str::removeRight((string) $this->model->absoluteUrl(), '/');

        return $current !== '' && $current === $home;
    }

    public function canonicalUrl()
    {
        $url = URL::tidy(Str::trim($this->explicitUrl ?? $this->data->get('canonical_url')));

        if (! Str::startsWith($url, Site::current()?->absoluteUrl() ?? config('app.url'))) {
            return $url;
        }

        if (! $paginator = Blink::get('tag-paginator')) {
            return $url;
        }

        $paginator->setPath($url);

        $page = $paginator->currentPage();

        switch (true) {
            case config('statamic.seo-pro.pagination') === false:
            case config('statamic.seo-pro.pagination.enabled_in_canonical_url') === false:
            case config('statamic.seo-pro.pagination.enabled_on_first_page') === false && $page === 1:
                return $url;
        }

        return URL::makeAbsolute($paginator->url($page));
    }

    protected function prevUrl()
    {
        if (config('statamic.seo-pro.pagination') === false) {
            return null;
        }

        $url = Str::trim($this->data->get('canonical_url'));

        if (! Str::startsWith($url, Site::current()?->absoluteUrl() ?? config('app.url'))) {
            return $url;
        }

        if (! $paginator = Blink::get('tag-paginator')) {
            return null;
        }

        if (config('statamic.seo-pro.pagination.enabled_on_first_page') === false && $paginator->currentPage() === 2) {
            return $url;
        }

        if (! $prevUrl = $paginator->previousPageUrl()) {
            return null;
        }

        return URL::makeAbsolute($prevUrl);
    }

    protected function nextUrl()
    {
        if (config('statamic.seo-pro.pagination') === false) {
            return null;
        }

        $url = Str::trim($this->data->get('canonical_url'));

        if (! Str::startsWith($url, Site::current()?->absoluteUrl() ?? config('app.url'))) {
            return $url;
        }

        if (! $paginator = Blink::get('tag-paginator')) {
            return null;
        }

        if (! $nextUrl = $paginator->nextPageUrl()) {
            return null;
        }

        return URL::makeAbsolute($nextUrl);
    }

    protected function parse(
        string $key,
        mixed $item,
        bool $hasAttemptedToFallbackToSection = false
    ) {
        $original = $item;

        if (is_array($item)) {
            return array_map(function ($item) use ($key) {
                return $this->parse($key, $item);
            }, $item);
        }

        // Get raw value for string checks.
        $raw = $item instanceof Value
            ? $item->raw()
            : $item;

        // If they have antlers in the string, they are on their own.
        if (is_string($raw) && Str::contains($raw, '{{')) {
            return $this->parseAntlers($raw);
        }

        // For source-based strings, we should get the value from the source.
        if (is_string($raw) && Str::startsWith($raw, '@seo:')) {
            $field = explode('@seo:', $raw)[1];

            if (Str::contains($field, '/')) {
                $field = explode('/', $field)[1];
            }

            $item = Arr::get($this->current, $field);

            if ($item instanceof Value) {
                if ($item->fieldtype() instanceof Bard) {
                    $item = (string) Statamic::modify($item)->bardText();
                } else {
                    $item = $item->value();
                }
            }

            // When the field is empty, attempt to fall back to the section or site defaults.
            if (! $item) {
                if (
                    ! $hasAttemptedToFallbackToSection
                    && isset($this->sectionDefaults[$key])
                    && $this->sectionDefaults[$key] !== $original
                ) {
                    return $this->parse($key, $this->sectionDefaults[$key], hasAttemptedToFallbackToSection: true);
                }

                if (isset($this->siteDefaults[$key]) && $this->siteDefaults[$key] !== $original) {
                    return $this->parse($key, $this->siteDefaults[$key], $hasAttemptedToFallbackToSection);
                }
            }
        }

        // If we have a method here to perform additional parsing, do that now.
        // eg. Limit a string to n characters.
        if (method_exists($this, $method = 'parse'.ucfirst($key).'Field')) {
            $item = $this->$method($item);
        }

        return $item;
    }

    protected function compiledTitle()
    {
        $title = Str::trim($this->data->get('title'));
        $siteName = Str::trim($this->data->get('site_name'));
        $siteNameSeparator = Str::trim($this->data->get('site_name_separator'));
        $siteNamePosition = (string) $this->data->get('site_name_position');

        if (! $title) {
            return $siteName;
        }

        if (config('statamic.seo-pro.pagination') !== false) {
            if ($paginator = Blink::get('tag-paginator')) {
                if ($paginator->currentPage() > 1) {
                    $title = __('seo-pro::meta.pagination_page', ['title' => $title, 'page' => $paginator->currentPage()]);
                }
            }
        }

        if (! $siteName || $siteNamePosition === 'none') {
            return $title;
        }

        $compiled = collect([$title, $siteNameSeparator, $siteName]);

        if ($siteNamePosition === 'before') {
            $compiled = $compiled->reverse();
        }

        return $compiled->implode(' ');
    }

    protected function ogTitle()
    {
        if ($title = $this->data->get('og_title')) {
            return $title;
        }

        if ($title = $this->data->get('title')) {
            return $title;
        }

        return $this->compiledTitle();
    }

    protected function twitterTitle()
    {
        if ($title = $this->data->get('twitter_title')) {
            return $title;
        }

        return $this->data->get('title');
    }

    protected function twitterDescription()
    {
        if ($description = $this->data->get('twitter_description')) {
            return $description;
        }

        return $this->data->get('description');
    }

    protected function lastModified()
    {
        return method_exists($this->model, 'lastModified')
            ? $this->model->lastModified()
            : null;
    }

    protected function site()
    {
        return method_exists($this->model, 'site')
            ? $this->model->site()
            : Site::default();
    }

    protected function alternateLocales()
    {
        if (config('statamic.seo-pro.alternate_locales') === false) {
            return [];
        } elseif (config('statamic.seo-pro.alternate_locales.enabled') === false) {
            return [];
        } elseif (! $this->model) {
            return [];
        }

        $alternateLocales = collect(Config::getOtherLocales($this->model->locale()))
            ->filter(fn ($locale) => $this->model->in($locale))
            ->filter(fn ($locale) => $this->model->in($locale)->status() === 'published')
            ->reject(fn ($locale) => collect(config('statamic.seo-pro.alternate_locales.excluded_sites'))->contains($locale))
            ->map(function ($locale) {
                return [
                    'site' => $site = Site::get($locale),
                    'is_default_site' => $site->isDefault(),
                    'url' => $this->model->in($locale)->absoluteUrl(),
                ];
            });

        $duplicates = $alternateLocales
            ->merge([['site' => $this->site()]])
            ->groupBy(fn ($locale) => $locale['site']->shortLocale())
            ->filter(fn ($locales) => $locales->count() > 1)
            ->keys();

        $alternateLocales->transform(function ($locale) use ($duplicates) {
            return array_merge($locale, [
                'hreflang' => $duplicates->contains($locale['site']->shortLocale())
                    ? $this->formatHreflangLocale($locale['site']->locale())
                    : $locale['site']->shortLocale(),
            ]);
        });

        return $alternateLocales->all();
    }

    protected function currentHreflang($alternateLocales)
    {
        $currentShortLocale = $this->site()->shortLocale();

        $alternateShortLocales = collect($alternateLocales)
            ->map(fn ($locale) => $locale['site']->shortLocale());

        if ($alternateShortLocales->contains($currentShortLocale)) {
            return $this->formatHreflangLocale($this->site()->locale());
        }

        return $currentShortLocale;
    }

    protected function formatHreflangLocale($locale)
    {
        return strtolower(str_replace('_', '-', $locale));
    }

    /** @noinspection PhpUnused */
    protected function parseTitleField($value)
    {
        if ($value instanceof Value) {
            $value = $value->value();
        }

        return trim($value);
    }

    /** @noinspection PhpUnused */
    protected function parseDescriptionField($value)
    {
        if ($value instanceof Value) {
            $value = $value->value();
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim(strip_tags($value));

        if (strlen($value) > 320) {
            $value = substr($value, 0, 320).'...';
        }

        return iconv('UTF-8', 'UTF-8//IGNORE', $value);
    }

    /** @noinspection PhpUnused */
    protected function parseImageField($value)
    {
        return $value instanceof Collection || $value instanceof Builder
            ? $value->first()
            : $value;
    }

    protected function parseAntlers($item)
    {
        try {
            return (string) Antlers::parse($item, array_merge(
                app(ViewCascade::class)->toArray(),
                $this->current ?? [],
            ));
        } catch (RuntimeException $e) {
            return $item;
        }
    }

    protected function parseJsonLdSchema($item)
    {
        if ($item instanceof Value) {
            $item = $item->raw();
        }

        if (! is_string($item) || ! Str::contains($item, '{{')) {
            return $item;
        }

        try {
            return (string) Antlers::parse($item, array_merge(
                app(ViewCascade::class)->toArray(),
                $this->current ?? [],
                ['seo' => $this->data->all()],
            ));
        } catch (RuntimeException $e) {
            return $item;
        }
    }

    private function hydrateCascade()
    {
        $cascade = app(ViewCascade::class);

        // Hydrate if not already hydrated.
        // Determine if it's already hydrated by seeing if there's an arbitrary value already in there.
        if (! $cascade->get('now')) {
            $cascade->hydrate();
        }
    }

    protected function humans()
    {
        if (config('statamic.seo-pro.humans.enabled')) {
            return URL::makeAbsolute(Str::ensureLeft(config('statamic.seo-pro.humans.url'), '/'));
        }
    }

    protected function robots()
    {
        $entryHasLegacyRobots = $this->data->has('robots')
            && ! isset($this->siteDefaults['robots'])
            && ! isset($this->sectionDefaults['robots']);

        $entryHasNewRobotsFields = (
            $this->data->has('robots_indexing')
            && ! isset($this->siteDefaults['robots_indexing'])
            && ! isset($this->sectionDefaults['robots_indexing'])
        ) || (
            $this->data->has('robots_following')
            && ! isset($this->siteDefaults['robots_following'])
            && ! isset($this->sectionDefaults['robots_following'])
        );

        if ($entryHasLegacyRobots && ! $entryHasNewRobotsFields) {
            return $this->getLegacyRobots();
        }

        $robots = [];

        if ($indexing = $this->data->get('robots_indexing')) {
            $robots[] = $indexing;
        }

        if ($following = $this->data->get('robots_following')) {
            $robots[] = $following;
        }

        if ($this->data->get('robots_noarchive')) {
            $robots[] = 'noarchive';
        }

        if ($this->data->get('robots_noimageindex')) {
            $robots[] = 'noimageindex';
        }

        if ($this->data->get('robots_nosnippet')) {
            $robots[] = 'nosnippet';
        }

        if (! empty($robots)) {
            return $robots;
        }

        return $this->getLegacyRobots();
    }

    protected function getLegacyRobots(): array
    {
        if (! $this->data->has('robots')) {
            return [];
        }

        $robots = $this->data->get('robots');

        if ($robots instanceof Value) {
            $robots = $robots->value();
        }

        if (is_array($robots) && isset($robots[0]['key'])) {
            return collect($robots)->pluck('key')->toArray();
        }

        return is_array($robots) ? $robots : [];
    }

    protected function robotsIndexing()
    {
        return in_array('noindex', $this->robots()) ? 'noindex' : 'index';
    }

    protected function jsonLd()
    {
        $snippets = collect();
        $jsonLdEntity = $this->data->get('json_ld_entity', 'organization');

        if ($this->isHomePage() && $jsonLdEntity !== 'disabled') {
            if ($entity = $this->buildEntitySchema($jsonLdEntity)) {
                $snippets->push(json_encode($entity, JSON_UNESCAPED_SLASHES));
            }
        }

        if ($this->data->get('json_ld_breadcrumbs') && request()->segment(1)) {
            $breadcrumbs = Statamic::tag('nav:breadcrumbs')->fetch();

            $snippets->push(json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => $breadcrumbs->values()->map(function ($crumb, $index) {
                    return [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'name' => $crumb->title,
                        'item' => $crumb->absoluteUrl(),
                    ];
                })->all(),
            ], JSON_UNESCAPED_SLASHES));
        }

        if ($jsonLdSchema = $this->data->get('json_ld_schema')) {
            $snippets->push($jsonLdSchema);
        }

        return $snippets;
    }

    protected function buildEntitySchema(string $entity): ?array
    {
        $type = match ($entity) {
            'organization' => 'Organization',
            'person' => 'Person',
            'local_business' => 'LocalBusiness',
            'corporation' => 'Corporation',
            default => null,
        };

        if (! $type || ! $name = $this->jsonLdEntityValue('json_ld_entity_name')) {
            return null;
        }

        $imageKey = $entity === 'person' ? 'image' : 'logo';

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $type,
            'name' => $name,
            '@id' => $this->homeUrl().Str::slug($type),
            'url' => $this->jsonLdEntityValue('json_ld_entity_url') ?: $this->homeUrl(),
            'alternateName' => $this->jsonLdEntityValue('json_ld_entity_alternate_name'),
            'description' => $this->jsonLdEntityValue('json_ld_entity_description'),
            $imageKey => $this->jsonLdEntityImageUrl($this->resolveJsonLdEntityLogo()),
            'telephone' => $this->jsonLdEntityValue('json_ld_entity_telephone'),
            'email' => $this->jsonLdEntityValue('json_ld_entity_email'),
            'address' => $this->buildJsonLdEntityAddress(),
            'geo' => $this->buildJsonLdEntityGeo(),
            'sameAs' => $this->buildJsonLdEntitySameAs(),
        ];

        if ($entity === 'local_business') {
            $schema['priceRange'] = $this->jsonLdEntityValue('json_ld_entity_price_range');
            $schema['openingHoursSpecification'] = $this->buildJsonLdEntityOpeningHours();
        }

        if ($entity === 'corporation') {
            $schema['tickerSymbol'] = $this->jsonLdEntityValue('json_ld_entity_ticker_symbol');
        }

        return array_filter($schema);
    }

    protected function jsonLdEntityValue(string $key): string
    {
        $value = $this->data->get($key);

        if ($value instanceof Value) {
            $value = $value->value();
        }

        return is_scalar($value) ? trim((string) $value) : '';
    }

    protected function buildJsonLdEntityAddress(): ?array
    {
        $address = array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $this->jsonLdEntityValue('json_ld_entity_street_address'),
            'addressLocality' => $this->jsonLdEntityValue('json_ld_entity_locality'),
            'addressRegion' => $this->jsonLdEntityValue('json_ld_entity_region'),
            'postalCode' => $this->jsonLdEntityValue('json_ld_entity_postal_code'),
            'addressCountry' => $this->jsonLdEntityValue('json_ld_entity_country'),
        ]);

        return count($address) > 1 ? $address : null;
    }

    protected function buildJsonLdEntityGeo(): ?array
    {
        $latitude = $this->jsonLdEntityValue('json_ld_entity_latitude');
        $longitude = $this->jsonLdEntityValue('json_ld_entity_longitude');

        if (! $latitude || ! $longitude) {
            return null;
        }

        return [
            '@type' => 'GeoCoordinates',
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];
    }

    protected function buildJsonLdEntitySameAs(): array
    {
        $sameAs = $this->data->get('json_ld_entity_same_as');

        if ($sameAs instanceof Value) {
            $sameAs = $sameAs->value();
        }

        return collect($sameAs)->filter()->values()->all();
    }

    protected function buildJsonLdEntityOpeningHours(): array
    {
        $hours = $this->data->get('json_ld_entity_opening_hours');

        if ($hours instanceof Value) {
            $hours = $hours->value();
        }

        if (! is_array($hours)) {
            return [];
        }

        $days = [
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday',
        ];

        return collect($hours)
            ->map(function ($times, $day) use ($days) {
                if (! isset($days[$day]) || empty($times['opening']) || empty($times['closing'])) {
                    return null;
                }

                return [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => 'https://schema.org/'.$days[$day],
                    'opens' => $times['opening'],
                    'closes' => $times['closing'],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function resolveJsonLdEntityLogo()
    {
        $logo = $this->data->get('json_ld_entity_logo');

        if ($logo instanceof Value) {
            $logo = $logo->value();
        }

        if ($logo instanceof Collection || $logo instanceof Builder) {
            $logo = $logo->first();
        }

        if (is_array($logo)) {
            $logo = $logo[0] ?? null;
        }

        if ($logo && ! $logo instanceof AssetContract) {
            $logo = Asset::find($logo);
        }

        return $logo ?: null;
    }

    protected function jsonLdEntityImageUrl($logo)
    {
        if (! $logo) {
            return null;
        }

        if (config('statamic.seo-pro.json_ld.use_glide_for_logo', true)) {
            return Statamic::tag('glide')->src($logo)->square(512)->absolute(true)->fetch();
        }

        return $logo->absoluteUrl();
    }

    protected function augmentData($data)
    {
        // It's a big performance hit to augment entries & terms for a sitemap,
        // when we only need the augmented `permalink`; So here we bypass the
        // augmentation and just augment what's actually used by the sitemap.
        if ($this->forSitemap) {
            return [
                'permalink' => $data->absoluteUrl(),
            ];
        }

        return $data->toAugmentedArray();
    }
}
