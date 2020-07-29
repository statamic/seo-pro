<?php

namespace Statamic\SeoPro\Reporting\Rules\Concerns;

use Statamic\SeoPro\Reporting\Page;

trait FailsWhenPagesDontPass
{
    protected $failures;

    public function processSite()
    {
        $this->failures = $this->report->pages()->filter(function ($page) {
            return ! $this->passesPageRule($page);
        })->count();
    }

    protected function passesPageRule(Page $page)
    {
        $rule = new static;

        $rule
            ->setPage($page)
            ->setReport($this->report)
            ->load($page->results()[$this->id()]);

        return $rule->status() === 'pass';
    }

    public function saveSite()
    {
        return $this->failures;
    }

    public function loadSite($data)
    {
        $this->failures = $data;
    }

    public function siteStatus()
    {
        return $this->failures === 0 ? 'pass' : 'fail';
    }
}
