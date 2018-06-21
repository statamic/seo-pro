<?php

namespace Statamic\Addons\SeoPro\Reporting\Rules\Page;

use Statamic\Addons\SeoPro\Reporting\Page;

class ThreeSegmentUrls extends Rule
{
    protected $unique;

    public function description()
    {
        return 'URLs should be a maximum of 3 segments.';
    }

    protected function warningComment()
    {
        return sprintf('URL has %s segments.', $this->count);
    }

    public function process()
    {
        $url = parse_url($this->page->url())['path'];
        $this->count = substr_count($url, '/');
    }

    public function status()
    {
        return $this->count <= 3 ? 'pass' : 'warning';
    }

    public function save()
    {
        return $this->count;
    }

    public function load($saved)
    {
        $this->count = $saved;
    }
}
