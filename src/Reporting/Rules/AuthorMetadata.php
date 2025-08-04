<?php

namespace Statamic\SeoPro\Reporting\Rules;

use Statamic\SeoPro\Reporting\Rule;

class AuthorMetadata extends Rule
{
    use Concerns\WarnsWhenPagesDontPass;

    public function siteDescription()
    {
        return __('seo-pro::messages.rules.author_metadata.description');
    }

    public function pageDescription()
    {
        return __('seo-pro::messages.rules.author_metadata.description');
    }

    public function siteWarningComment()
    {
        return __('seo-pro::messages.rules.author_metadata.warning', ['count' => $this->failures]);
    }

    public function pageWarningComment()
    {
        return __('seo-pro::messages.rules.author_metadata.warning');
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
        $author = $this->page->get('author');
        return empty($author) ? 'warning' : 'pass';
    }

    public function maxPoints()
    {
        return $this->report->pages()->count();
    }

    public function demerits()
    {
        return $this->failures * 0.5;
    }

    public function actionablePill()
    {
        return __('seo-pro::messages.rules.author_metadata.pill');
    }
}