<?php

namespace Statamic\Addons\SeoPro\Reporting\Rules;

use Statamic\API\Str;
use Statamic\Addons\SeoPro\Reporting\Rule;

class ThreeSegmentUrls extends Rule
{
    use Concerns\WarnsWhenPagesDontPass;

    protected $slashes;

    public function siteDescription()
    {
        return 'Page URLs should be a maximum of 3 segments.';
    }

    public function pageDescription()
    {
        return 'The URL should be a maximum of 3 segments.';
    }

    public function siteWarningComment()
    {
        return sprintf('%s pages with more than 3 segments in their URLs.', $this->failures);
    }

    public function processPage()
    {
        $url = parse_url($this->page->url())['path'];
        $this->slashes = substr_count($url, '/');
    }

    public function pageStatus()
    {
        return $this->slashes <= 3 ? 'pass' : 'warning';
    }

    public function siteStatus()
    {
        return $this->failures === 0 ? 'pass' : 'warning';
    }

    public function savePage()
    {
        return $this->slashes;
    }

    public function loadPage($data)
    {
        $this->slashes = $data;
    }
}
