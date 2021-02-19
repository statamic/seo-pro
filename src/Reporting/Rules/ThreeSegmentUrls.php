<?php

namespace Statamic\SeoPro\Reporting\Rules;

use Statamic\SeoPro\Reporting\Rule;

class ThreeSegmentUrls extends Rule
{
    use Concerns\WarnsWhenPagesDontPass;

    protected $slashes;

    public function siteDescription()
    {
        return __('seo-pro::messages.rules.three_segment_urls_site');
    }

    public function pageDescription()
    {
        return __('seo-pro::messages.rules.three_segment_urls_page');
    }

    public function siteWarningComment()
    {
        return __('seo-pro::messages.rules.three_segment_urls_site_warning', ['count' => $this->failures]);
    }

    public function processPage()
    {
        $url = parse_url($this->page->url())['path'] ?? '/';
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
