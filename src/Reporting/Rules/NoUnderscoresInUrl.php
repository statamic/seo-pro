<?php

namespace Statamic\SeoPro\Reporting\Rules;

use Statamic\SeoPro\Reporting\Rule;
use Statamic\Support\Str;

class NoUnderscoresInUrl extends Rule
{
    use Concerns\FailsWhenPagesDontPass;

    protected $passes;

    public function siteDescription()
    {
        return __('seo-pro::messages.rules.no_underscores_in_url_site');
    }

    public function pageDescription()
    {
        return __('seo-pro::messages.rules.no_underscores_in_url_page');
    }

    public function siteFailingComment()
    {
        return __('seo-pro::messages.rules.no_underscores_in_url_site_failing', ['count' => $this->failures]);
    }

    public function processPage()
    {
        $this->passes = ! Str::contains($this->page->url(), '_');
    }

    public function pageStatus()
    {
        return $this->passes ? 'pass' : 'fail';
    }

    public function savePage()
    {
        return $this->passes;
    }

    public function loadPage($data)
    {
        $this->passes = $data;
    }
}
