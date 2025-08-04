<?php

namespace Statamic\SeoPro\Reporting\Rules;

use Statamic\SeoPro\Reporting\Rule;

class OpenGraphMetadata extends Rule
{
    use Concerns\FailsWhenPagesDontPass;

    public function siteDescription()
    {
        return __('seo-pro::messages.rules.open_graph_metadata.description');
    }

    public function pageDescription()
    {
        return __('seo-pro::messages.rules.open_graph_metadata.description');
    }

    public function siteFailingComment()
    {
        return __('seo-pro::messages.rules.open_graph_metadata.fail', ['count' => $this->failures]);
    }

    public function pageFailingComment()
    {
        return __('seo-pro::messages.rules.open_graph_metadata.fail');
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
        $ogImage = $this->page->get('og_image');
        
        // og_type defaults to 'website' in the Cascade, so we only check for image
        return empty($ogImage) ? 'fail' : 'pass';
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
        return __('seo-pro::messages.rules.open_graph_metadata.pill');
    }
}