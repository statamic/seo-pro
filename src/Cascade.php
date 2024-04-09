<?php

namespace Statamic\SeoPro;

use Illuminate\Support\Collection;
use Statamic\Facades\Blink;
use Statamic\Facades\Config;
use Statamic\Facades\Entry;
use Statamic\Facades\Parse;
use Statamic\Facades\Site;
use Statamic\Facades\URL;
use Statamic\Fields\Value;
use Statamic\Support\Arr;
use Statamic\Support\Str;
use Statamic\View\Cascade as ViewCascade;

class Cascade
{
    protected $data;
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
            return $this->parse($key, $item);
        });

        return $this->data->merge([
            'compiled_title' => $this->compiledTitle(),
            'og_title' => $this->ogTitle(),
            'canonical_url' => $this->canonicalUrl(),
            'prev_url' => $this->prevUrl(),
            'next_url' => $this->nextUrl(),
            'home_url' => URL::makeAbsolute('/'),
            'humans_txt' => $this->humans(),
            'site' => $this->site(),
            'alternate_locales' => $this->alternateLocales(),
            'last_modified' => $this->lastModified(),
            'twitter_card' => config('statamic.seo-pro.twitter.card'),
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

    public function canonicalUrl()
    {
        $url = Str::trim($this->explicitUrl ?? $this->data->get('canonical_url'));

        if (! Str::startsWith($url, config('app.url'))) {
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

        if (! Str::startsWith($url, config('app.url'))) {
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

        if (! Str::startsWith($url, config('app.url'))) {
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

    protected function parse($key, $item)
    {
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
        if (is_string($raw) && Str::contains($item, '{{')) {
            return $this->parseAntlers($item);
        }

        // For source-based strings, we should get the value from the source.
        if (is_string($raw) && Str::startsWith($item, '@seo:')) {
            $field = explode('@seo:', $item)[1];

            if (Str::contains($field, '/')) {
                $field = explode('/', $field)[1];
            }

            $item = Arr::get($this->current, $field);

            if ($item instanceof Value) {
                $item = $item->value();
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
        if ($title = $this->data->get('title')) {
            return $title;
        }

        return $this->compiledTitle();
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

        return collect(Config::getOtherLocales($this->model->locale()))
            ->filter(function ($locale) {
                return $this->model->in($locale);
            })
            ->filter(function ($locale) {
                return $this->model->in($locale)->status() === 'published';
            })
            ->reject(function ($locale) {
                return collect(config('statamic.seo-pro.alternate_locales.excluded_sites'))->contains($locale);
            })
            ->map(function ($locale) {
                return [
                    'site' => Config::getSite($locale),
                    'url' => $this->model->in($locale)->absoluteUrl(),
                ];
            })
            ->all();
    }

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

    protected function parseImageField($value)
    {
        return $value instanceof Collection
            ? $value->first()
            : $value;
    }

    protected function parseAntlers($item)
    {
        $viewCascade = app(ViewCascade::class)->toArray();

        return (string) Parse::template($item, array_merge($viewCascade, $this->current));
    }

    protected function humans()
    {
        if (config('statamic.seo-pro.humans.enabled')) {
            return URL::makeAbsolute(Str::ensureLeft(config('statamic.seo-pro.humans.url'), '/'));
        }
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
