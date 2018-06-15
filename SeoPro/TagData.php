<?php

namespace Statamic\Addons\SeoPro;

class TagData
{
    protected $data;

    public function __construct()
    {
        $this->data = collect();
    }

    public function with($array)
    {
        $this->data = $this->data->merge($array);

        return $this;
    }

    public function get()
    {
        return $this->data->merge([
            'compiled_title' => $this->compiledTitle(),
        ])->all();
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
