<?php

namespace Statamic\SeoPro\Reporting\Rules;

use Statamic\SeoPro\Reporting\Rule;

class IdealTitleLength extends Rule
{
    use Concerns\FailsWhenPagesDontPass;

    protected $length;

    public function actionablePill()
    {
        return __('seo-pro::messages.rules.title_length_actionable_pill');
    }

    public function siteDescription()
    {
        return __('seo-pro::messages.rules.title_length_site');
    }

    public function pageDescription()
    {
        return __('seo-pro::messages.rules.title_length_page');
    }

    public function siteFailingComment()
    {
        return trans_choice(
            'seo-pro::messages.rules.title_length_site_failing',
            $this->failures,
            ['count' => $this->failures]
        );
    }

    public function pageFailingComment()
    {
        $config = config('seo-pro.reports.title_length');
        $passMax = $config['pass_max'] ?? 70;

        if ($this->length === 0) {
            return __('seo-pro::messages.rules.title_length_page_failing_missing');
        }

        return __('seo-pro::messages.rules.title_length_page_failing_too_long', [
            'length' => $this->length,
            'max' => $passMax,
        ]);
    }

    public function pagePassingComment()
    {
        return __('seo-pro::messages.rules.title_length_page_passing', ['length' => $this->length]);
    }

    public function processPage()
    {
        $title = $this->page->get('title');
        $this->length = $title ? mb_strlen($title) : 0;
    }

    public function pageStatus()
    {
        $config = config('seo-pro.reports.title_length');
        $passMax = $config['pass_max'] ?? 70;

        if ($this->length === 0) {
            return 'fail';
        }

        return $this->length <= $passMax ? 'pass' : 'fail';
    }

    public function savePage()
    {
        return $this->length;
    }

    public function loadPage($data)
    {
        $this->length = $data;
    }
}
