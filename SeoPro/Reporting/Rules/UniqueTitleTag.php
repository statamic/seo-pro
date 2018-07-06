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
        return sprintf('%s pages with duplicate titles.', $this->failures);
    }

    public function pageFailingComment()
    {
        return sprintf('%s pages with "%s" as the title.', $this->count, $this->title());
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

    public function maxPoints()
    {
        return $this->points() * $this->report->pages()->count();
    }

    public function demerits()
    {
        return $this->points() * $this->failures;
    }

    protected function points()
    {
        return 2;
    }
}
