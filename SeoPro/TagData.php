<?php

namespace Statamic\Addons\SeoPro;

use Statamic\API\Str;
use Statamic\API\URL;
use Statamic\API\Data;
use Statamic\API\Page;
use Statamic\API\Parse;
use Statamic\API\Config;
use Statamic\Routing\Route;
use Statamic\Contracts\Data\Data as DataContract;

class TagData
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
        if ($data instanceof DataContract || $data instanceof Route) {
            $this->current = $data->toArray();
            $this->model = $data;
        } else {
            $this->current = $data;
            $this->model = Data::find($data['id']);
        }

        return $this;
    }

    public function get()
    {
        if (! $this->current) {
            $this->withCurrent(Page::whereUri('/'));
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

    public function canonicalUrl() {
        $url = $this->model->absoluteUrl();

        // Include pagination if present
        if (app('request')->has('page')) {
            $url .= '?page=' . app('request')->get('page');
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

        // If they have antlers in the string, they are on their own.
        if (Str::contains($item, '{{')) {
            return Parse::template($item, $this->current);
        }

        // For source-based strings, we should get the value from the source.
        if (Str::startsWith($item, '@seo:')) {
            $field = explode('@seo:', $item)[1];

            if (Str::contains($field, '/')) {
                $field = explode('/', $field)[1];
            }

            $item = array_get($this->current, $field);
        }

        // If we have a method here to perform additional parsing, do that now.
        // eg. Limit a string to n characters.
        if (method_exists($this, $method = 'parse' . ucfirst($key) . 'Field')) {
            $item = $this->$method($item);
        }

        return $item;
    }

    protected function compiledTitle()
    {
        $siteName = $this->data->get('site_name');

        if (! $title = $this->data->get('title')) {
            return $siteName;
        }

        if (! $siteName) {
            return $title;
        }

        $compiled = '';
        $separator = $this->data->get('site_name_separator');

        if ($this->data->get('site_name_position') === 'before') {
            $compiled .= $siteName . ' ' . $separator . ' ';
        }

        $compiled .= $title;

        if ($this->data->get('site_name_position') === 'after') {
            $compiled .= ' ' . $separator . ' ' . $siteName;
        }

        return $compiled;
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
        if (method_exists($this->model, 'lastModified')) {
            return $this->model->lastModified();
        }
    }

    protected function locale()
    {
        if (! method_exists($this->model, 'locale')) {
            return site_locale();
        }

        return Config::getShortLocale($this->model->locale());
    }

    protected function alternateLocales()
    {
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
        $value = strip_tags($value);

        if (strlen($value) > 320) {
            $value = substr($value, 0, 320) . '...';
        }

        return iconv("UTF-8", "UTF-8//IGNORE", $value);
    }

    protected function humans()
    {
        $config = Settings::load()->get('humans');

        if (array_get($config, 'enabled')) {
            return URL::makeAbsolute('humans.txt');
        }
    }
}
