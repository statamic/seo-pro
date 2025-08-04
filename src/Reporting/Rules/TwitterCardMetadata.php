<?php

namespace Statamic\SeoPro\Reporting\Rules;

use Statamic\SeoPro\Reporting\Rule;

class TwitterCardMetadata extends Rule
{
    use Concerns\FailsOrWarnsWhenPagesDontPass;

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
        return __('seo-pro::messages.rules.twitter_card_metadata.warning', ['count' => $this->warnings]);
    }

    public function pageWarningComment()
    {
        return __('seo-pro::messages.rules.twitter_card_metadata.warning');
    }

    public function siteFailingComment()
    {
        return __('seo-pro::messages.rules.twitter_card_metadata.fail', ['count' => $this->failures]);
    }

    public function pageFailingComment()
    {
        return __('seo-pro::messages.rules.twitter_card_metadata.fail');
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
        $twitterTitle = $this->page->get('twitter_title');
        $twitterCard = $this->page->get('twitter_card');

        // Description is optional for Twitter Cards
        $missingCount = 0;
        if (empty($twitterTitle)) $missingCount++;
        if (empty($twitterCard)) $missingCount++;

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
        return __('seo-pro::messages.rules.twitter_card_metadata.pill');
    }
}