<?php

namespace Statamic\Addons\SeoPro;

use Statamic\API\Str;
use Statamic\API\URL;
use Statamic\API\Data;
use Statamic\API\Page;
use Statamic\API\Parse;
use Statamic\API\Config;
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
        if ($data instanceof DataContract) {
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

        $this->data = $this->data->map(function ($item, $key) {
            return $this->parse($key, $item);
        });

        return $this->data->merge([
            'compiled_title' => $this->compiledTitle(),
            'canonical_url' => $this->model->absoluteUrl(),
            'home_url' => URL::makeAbsolute('/'),
            'locale' => Config::getFullLocale($this->model->locale()),
            'alternate_locales' => $this->alternateLocales(),
        ])->all();
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
        $compiled = '';

        if ($this->data->get('site_name_position') === 'before') {
            $compiled .= $this->data->get('site_name') . ' | ';
        }

        $compiled .= $this->data->get('title');

        if ($this->data->get('site_name_position') === 'after') {
            $compiled .= ' | ' . $this->data->get('site_name');
        }

        return $compiled;
    }

    protected function alternateLocales()
    {
        $alternates = array_values(array_diff($this->model->locales(), [$this->model->locale()]));

        return array_map(function ($locale) {
            return Config::getFullLocale($locale);
        }, $alternates);
    }

    protected function parseDescriptionField($value)
    {
        $value = strip_tags($value);

        if (strlen($value) > 320) {
            $value = substr($value, 0, 320) . '...';
        }

        return $value;
    }
}
