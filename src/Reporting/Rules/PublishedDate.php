<?php

namespace Statamic\SeoPro\Reporting\Rules;

use Statamic\SeoPro\Reporting\Rule;

class PublishedDate extends Rule
{
    use Concerns\FailsWhenPagesDontPass;

    public function siteDescription()
    {
        return __('seo-pro::messages.rules.published_date.description');
    }

    public function pageDescription()
    {
        return __('seo-pro::messages.rules.published_date.description');
    }

    public function siteFailingComment()
    {
        return __('seo-pro::messages.rules.published_date.fail', ['count' => $this->failures]);
    }

    public function pageFailingComment()
    {
        return __('seo-pro::messages.rules.published_date.fail');
    }

    public function savePage()
    {
        return $this->pageStatus();
    }

    public function loadPage($data)
    {
        // Not used for this rule
    }

    public function pageStatus()
    {
        // Skip taxonomy/term pages and other non-entry pages
        $id = $this->page->get('id');
        if (is_string($id) && str_contains($id, '::')) {
            return 'pass'; // Taxonomy pages don't need published dates
        }
        
        // Check if this is a dated entry (has a date in the data)
        $publishedDate = $this->page->get('published_date');
        
        // If there's no published date but there is an updated date, 
        // it might be a page that doesn't need a published date
        $updatedDate = $this->page->get('updated_date');
        if (empty($publishedDate) && !empty($updatedDate)) {
            // This is likely a regular page, not a dated entry
            return 'pass';
        }
        
        return empty($publishedDate) ? 'fail' : 'pass';
    }

    public function maxPoints()
    {
        return $this->report->pages()->count();
    }

    public function demerits()
    {
        return $this->failures;
    }

    public function actionablePill()
    {
        return __('seo-pro::messages.rules.published_date.pill');
    }
}