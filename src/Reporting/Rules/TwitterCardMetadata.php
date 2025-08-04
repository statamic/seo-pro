<?php

namespace Statamic\SeoPro\Reporting\Rules;

use Statamic\SeoPro\Reporting\Rule;

class TwitterCardMetadata extends Rule
{
    use Concerns\WarnsWhenPagesDontPass;

    public function siteDescription()
    {
        return __('seo-pro::messages.rules.twitter_card_metadata.description');
    }

    public function pageDescription()
    {
        return __('seo-pro::messages.rules.twitter_card_metadata.description');
    }

    public function siteWarningComment()
    {
        return __('seo-pro::messages.rules.twitter_card_metadata.warning', ['count' => $this->failures]);
    }

    public function pageWarningComment()
    {
        return __('seo-pro::messages.rules.twitter_card_metadata.warning');
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
        $twitterImage = $this->page->get('twitter_image');
        
        // Only warn if twitter image is missing
        // Title/description fall back to regular values
        return empty($twitterImage) ? 'warning' : 'pass';
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
        return __('seo-pro::messages.rules.twitter_card_metadata.pill');
    }
}