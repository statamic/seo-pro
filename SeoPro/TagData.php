<?php

namespace Statamic\Addons\SeoPro;

use Statamic\API\Data;
use Statamic\API\Parse;

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
}
