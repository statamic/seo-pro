<?php

namespace Statamic\SeoPro;

use Illuminate\Support\Collection;
use Statamic\Facades\Config;
use Statamic\Facades\Entry;
use Statamic\Facades\Parse;
use Statamic\Facades\Site;
use Statamic\Facades\URL;
use Statamic\Fields\Value;
use Statamic\Support\Str;
use Statamic\View\Cascade as ViewCascade;

class Cascade
{
    protected $data;
    protected $current;
    protected $model;

    public function __construct()
    {
        $this->data = collect();
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

        $this->current = $data->toAugmentedArray();
        $this->model = $data;

        return $this;
    }

    public function get()
    {
        if (! $this->current) {
            $this->withCurrent(Entry::findByUri('/'));
        }

        if (array_get($this->current, 'response_code') === 404) {
            $this->current['title'] = '404 Page Not Found';
        }

        $this->data = $this->data->map(function ($item, $key) {
            return $this->parse($key, $item);
        });

        return $this->data->merge([
            'compiled_title' => $this->compiledTitle(),
            'og_title' => $this->ogTitle(),
            'canonical_url' => $this->canonicalUrl(),
            'home_url' => URL::makeAbsolute('/'),
            'humans_txt' => $this->humans(),
            'locale' => $this->locale(),
            'alternate_locales' => $this->alternateLocales(),
            'last_modified' => $this->lastModified(),
        ])->all();
    }

    public function value($key)
    {
        return $this->get()[$key] ?? null;
    }

    public function canonicalUrl()
    {
        $url = method_exists($this->model, 'absoluteUrl')
            ? $this->model->absoluteUrl()
            : config('app.url');

        // Include pagination if present
        if (app('request')->has('page')) {
            $url .= '?page='.((int) app('request')->get('page'));
        }

        return $url;
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

            $item = array_get($this->current, $field);

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
        $siteNameBefore = (string) $this->data->get('site_name_position') === 'before';

        if (! $title) {
            return $siteName;
        }

        if (! $siteName) {
            return $title;
        }

        $compiled = collect([$title, $siteNameSeparator, $siteName]);

        if ($siteNameBefore) {
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

    protected function locale()
    {
        return method_exists($this->model, 'locale')
            ? Config::getShortLocale($this->model->locale())
            : Site::default()->handle();
    }

    protected function alternateLocales()
    {
        if (! $this->model) {
            return [];
        }

        if (! method_exists($this->model, 'locales')) {
            return collect(Config::getOtherLocales())->map(function ($locale) {
                return ['locale' => $locale, 'url' => $this->model->absoluteUrl()];
            })->all();
        }

        $alternates = array_values(array_diff($this->model->locales(), [$this->model->locale()]));

        return collect($alternates)->map(function ($locale) {
            return [
                'locale' => Config::getShortLocale($locale),
                'url' => $this->model->in($locale)->absoluteUrl(),
            ];
        })->all();
    }

    protected function parseDescriptionField($value)
    {
        if ($value instanceof Value) {
            $value = $value->value();
        }

        if (! is_string($value)) {
            return null;
        }

        $value = strip_tags($value);

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
}
