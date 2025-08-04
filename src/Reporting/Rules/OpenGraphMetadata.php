<?php

namespace Statamic\SeoPro\Reporting\Rules;

use Statamic\SeoPro\Reporting\Rule;

class OpenGraphMetadata extends Rule
{
    use Concerns\FailsOrWarnsWhenPagesDontPass;

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

    public function siteWarningComment()
    {
        return __('seo-pro::messages.rules.open_graph_metadata.warning', ['count' => $this->warnings]);
    }

    public function pageWarningComment()
    {
        return __('seo-pro::messages.rules.open_graph_metadata.warning');
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
        $ogTitle = $this->page->get('og_title');
        $ogType = $this->page->get('og_type');

        // Description is optional for Open Graph
        $missingCount = 0;
        if (empty($ogTitle)) $missingCount++;
        if (empty($ogType)) $missingCount++;

        if ($missingCount >= 2) {
            return 'fail';
        } elseif ($missingCount > 0) {
            return 'warning';
        }

        return 'pass';
    }

    public function maxPoints()
    {
        return $this->report->pages()->count();
    }

    public function demerits()
    {
        return $this->failures + ($this->warnings * 0.5);
    }

    public function actionablePill()
    {
        return __('seo-pro::messages.rules.open_graph_metadata.pill');
    }
}