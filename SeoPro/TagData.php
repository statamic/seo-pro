<?php

namespace Statamic\Addons\SeoPro;

use Statamic\API\URL;
use Statamic\API\Data;
use Statamic\API\Parse;
use Statamic\API\Config;

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

    public function withCurrent($array)
    {
        $this->current = $array;
        $this->model = Data::find($array['id']);

        return $this;
    }

    public function get()
    {
        $this->data = $this->data->map(function ($item) {
            return $this->parse($item);
        });

        return $this->data->merge([
            'compiled_title' => $this->compiledTitle(),
            'canonical_url' => $this->model->absoluteUrl(),
            'home_url' => URL::makeAbsolute('/'),
            'locale' => Config::getFullLocale($this->model->locale()),
            'alternate_locales' => $this->alternateLocales(),
        ])->all();
    }

    protected function parse($item)
    {
        if (is_array($item)) {
            return array_map(function ($item) {
                return $this->parse($item);
            }, $item);
        }

        return Parse::template($item, $this->current);
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
}
