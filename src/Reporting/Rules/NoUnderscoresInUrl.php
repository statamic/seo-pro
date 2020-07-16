<?php

namespace Statamic\Addons\SeoPro\Reporting\Rules;

use Statamic\API\Str;
use Statamic\Addons\SeoPro\Reporting\Rule;

class NoUnderscoresInUrl extends Rule
{
    use Concerns\FailsWhenPagesDontPass;

    protected $passes;

    public function siteDescription()
    {
        return 'Page URLs should not contain underscores.';
    }

    public function pageDescription()
    {
        return 'The URL should not contain underscores.';
    }

    public function siteFailingComment()
    {
        return sprintf('%s pages with underscores in their URLs', $this->failures);
    }

    public function processPage()
    {
        $this->passes = !Str::contains($this->page->url(), '_');
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
