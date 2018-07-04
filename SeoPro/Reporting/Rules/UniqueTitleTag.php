<?php

namespace Statamic\Addons\SeoPro\Reporting\Rules;

use Statamic\Addons\SeoPro\Reporting\Rule;

class UniqueTitleTag extends Rule
{
    use Concerns\FailsWhenPagesDontPass;

    /**
     * Number of pages with this page's title.
     */
    protected $count;

    public function siteDescription()
    {
        return 'Each page should have a unique title tag.';
    }

    public function pageDescription()
    {
        return 'The title tag should be unique.';
    }

    public function siteFailingComment()
    {
        return sprintf('%s pages with the same title tag.', $this->count);
    }

    public function pageFailingComment()
    {
        return sprintf('%s pages with "%s" as the title.', $this->failures, $this->title());
    }

    public function pagePassingComment()
    {
        return $this->title();
    }

    public function processPage()
    {
        $this->count = $this->page->report()->pages()->filter(function ($page) {
            return $page->get('title') === $this->title();
        })->count();
    }

    public function savePage()
    {
        return $this->count;
    }

    public function loadPage($data)
    {
        $this->count = $data;
    }

    public function pageStatus()
    {
        return $this->count === 1 ? 'pass' : 'fail';
    }

    protected function title()
    {
        return $this->page->get('title');
    }
}
