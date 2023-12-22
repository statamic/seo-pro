<?php

namespace Statamic\SeoPro\Reporting\Rules;

use Statamic\Facades\Blink;
use Statamic\SeoPro\Reporting\Rule;

class UniqueMetaDescription extends Rule
{
    use Concerns\FailsWhenPagesDontPass;

    /**
     * Number of pages with this page's meta description.
     */
    protected $count;

    public function siteDescription()
    {
        return __('seo-pro::messages.rules.unique_description_site');
    }

    public function pageDescription()
    {
        return __('seo-pro::messages.rules.unique_description_page');
    }

    public function siteFailingComment()
    {
        return __('seo-pro::messages.rules.unique_description_site_failing', ['count' => $this->failures]);
    }

    public function pageFailingComment()
    {
        return __('seo-pro::messages.rules.unique_description_page_failing', [
            'count' => $this->count,
            'description' => $this->metaDescription(),
        ]);
    }

    public function pagePassingComment()
    {
        return $this->metaDescription();
    }

    public function processPage()
    {
        $this->count = $this
            ->groupAllPagesByDescription()
            ->get($this->metaDescription())
            ->count();
    }

    protected function groupAllPagesByDescription()
    {
        return Blink::once('seo-pro-report-'.$this->report->id().'-page-descriptions-grouped', function () {
            return $this->page->report()->pages()->mapToGroups(function ($page) {
                return [$page->get('description') => $page->id()];
            });
        });
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

    protected function metaDescription()
    {
        return $this->page->get('description');
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
        return 1;
    }
}
