<?php

namespace Statamic\SeoPro\Reporting\Rules;

use Statamic\SeoPro\Reporting\Rule;

class IdealMetaDescriptionLength extends Rule
{
    use Concerns\FailsWhenPagesDontPass;

    protected $length;
    protected $warnings;

    public function actionablePill()
    {
        return __('seo-pro::messages.rules.meta_description_length_actionable_pill');
    }

    public function siteDescription()
    {
        return __('seo-pro::messages.rules.meta_description_length_site');
    }

    public function pageDescription()
    {
        if (! isset($this->length) || $this->length === 0) {
            return __('seo-pro::messages.rules.meta_description_length_page_failing_missing');
        }

        $warnMin = config('statamic.seo-pro.reports.meta_description_length.warn_min', 120);
        $warnMax = config('statamic.seo-pro.reports.meta_description_length.warn_max', 240);

        if ($this->length < $warnMin) {
            return __('seo-pro::messages.rules.meta_description_length_page_failing_too_short', [
                'length' => $this->length,
                'min' => $warnMin,
            ]);
        }

        if ($this->length > $warnMax) {
            return __('seo-pro::messages.rules.meta_description_length_page_failing_too_long', [
                'length' => $this->length,
                'max' => $warnMax,
            ]);
        }

        return __('seo-pro::messages.rules.meta_description_length_page');
    }

    public function siteFailingComment()
    {
        return trans_choice(
            'seo-pro::messages.rules.meta_description_length_site_failing',
            $this->failures,
            ['count' => $this->failures]
        );
    }

    public function processSite()
    {
        $this->failures = $this->warnings = 0;

        $this->report->pages()->each(function ($page) {
            $rule = new static;

            $rule
                ->setPage($page)
                ->setReport($this->report)
                ->load($page->results()[$this->id()]);

            $status = $rule->status();

            if ($status === 'fail') {
                $this->failures++;
            } elseif ($status === 'warning') {
                $this->warnings++;
            }
        });
    }

    public function siteStatus()
    {
        if ($this->failures > 0) {
            return 'fail';
        }

        if ($this->warnings > 0) {
            return 'warning';
        }

        return 'pass';
    }

    public function saveSite()
    {
        return [
            'failures' => $this->failures,
            'warnings' => $this->warnings,
        ];
    }

    public function loadSite($data)
    {
        if (! $data) {
            return;
        }

        $this->failures = $data['failures'] ?? 0;
        $this->warnings = $data['warnings'] ?? 0;
    }

    public function pageFailingComment()
    {
        $warnMax = config('statamic.seo-pro.reports.meta_description_length', 240);

        if ($this->length === 0) {
            return __('seo-pro::messages.rules.meta_description_length_page_failing_missing');
        }

        return __('seo-pro::messages.rules.meta_description_length_page_failing_too_long', [
            'length' => $this->length,
            'max' => $warnMax,
        ]);
    }

    public function pagePassingComment()
    {
        return __('seo-pro::messages.rules.meta_description_length_page_passing', ['length' => $this->length]);
    }

    public function pageWarningComment()
    {
        return __('seo-pro::messages.rules.meta_description_length_page_warning', ['length' => $this->length]);
    }

    public function siteWarningComment()
    {
        return trans_choice(
            'seo-pro::messages.rules.meta_description_length_site_warning',
            $this->warnings,
            ['count' => $this->warnings]
        );
    }

    public function processPage()
    {
        $this->length = strlen($this->page->get('description', ''));
    }

    public function pageStatus()
    {
        $warnMin = config('statamic.seo-pro.reports.meta_description_length.warn_min', 120);
        $passMax = config('statamic.seo-pro.reports.meta_description_length.pass_max', 160);
        $warnMax = config('statamic.seo-pro.reports.meta_description_length.warn_max', 240);

        if ($this->length === 0) {
            return 'fail';
        }

        if ($this->length < $warnMin || ($this->length > $passMax && $this->length <= $warnMax)) {
            return 'warning';
        }

        if ($this->length > $warnMax) {
            return 'fail';
        }

        return 'pass';
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
