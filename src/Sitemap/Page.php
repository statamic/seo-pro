<?php

namespace Statamic\SeoPro\Sitemap;

class Page
{
    protected $data;

    public function with($data)
    {
        $this->data = collect($data);

        return $this;
    }

    public function path()
    {
        return parse_url($this->loc())['path'] ?? '/';
    }

    public function loc()
    {
        return $this->data->get('canonical_url');
    }

    public function lastmod()
    {
        return $this->data->get('last_modified')->format('Y-m-d');
    }

    public function changefreq()
    {
        return $this->data->get('change_frequency');
    }

    public function hrefLangs(): array
    {
        return $this->data->get('hreflangs', []);
    }

    public function priority()
    {
        return $this->data->get('priority');
    }

    public function toArray()
    {
        return [
            'path' => $this->path(),
            'loc' => $this->loc(),
            'lastmod' => $this->lastmod(),
            'changefreq' => $this->changefreq(),
            'priority' => $this->priority(),
            'hreflangs' => $this->hrefLangs(),
        ];
    }
}
