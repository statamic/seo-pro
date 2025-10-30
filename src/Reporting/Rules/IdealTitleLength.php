<?php

namespace Statamic\SeoPro\Reporting\Rules;

use Statamic\SeoPro\Reporting\Rule;

class IdealTitleLength extends Rule
{
    use Concerns\FailsWhenPagesDontPass;

    protected $length;
    protected $warnings;

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
        if (! isset($this->length) || $this->length === 0) {
            return __('seo-pro::messages.rules.title_length_page_failing_missing');
        }

        $config = config('seo-pro.reports.title_length');
        $warnMax = $config['warn_max'] ?? 70;

        if ($this->length > $warnMax) {
            return __('seo-pro::messages.rules.title_length_page_failing_too_long', [
                'length' => $this->length,
                'max' => $warnMax,
            ]);
        }

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
        if (is_array($data)) {
            $this->failures = $data['failures'] ?? 0;
            $this->warnings = $data['warnings'] ?? 0;
        } else {
            // Legacy support: old format was just a count of failures
            $this->failures = $data ?? 0;
            $this->warnings = 0;
        }
    }

    public function pageFailingComment()
    {
        $config = config('seo-pro.reports.title_length');
        $warnMax = $config['warn_max'] ?? 70;

        if ($this->length === 0) {
            return __('seo-pro::messages.rules.title_length_page_failing_missing');
        }

        return __('seo-pro::messages.rules.title_length_page_failing_too_long', [
            'length' => $this->length,
            'max' => $warnMax,
        ]);
    }

    public function pagePassingComment()
    {
        return __('seo-pro::messages.rules.title_length_page_passing', ['length' => $this->length]);
    }

    public function pageWarningComment()
    {
        return __('seo-pro::messages.rules.title_length_page_warning', ['length' => $this->length]);
    }

    public function siteWarningComment()
    {
        return trans_choice(
            'seo-pro::messages.rules.title_length_site_warning',
            $this->warnings,
            ['count' => $this->warnings]
        );
    }

    public function processPage()
    {
        $title = $this->page->get('title');
        $this->length = $title ? mb_strlen($title) : 0;
    }

    public function pageStatus()
    {
        $config = config('seo-pro.reports.title_length');
        $warnMin = $config['warn_min'] ?? 30;
        $passMax = $config['pass_max'] ?? 60;
        $warnMax = $config['warn_max'] ?? 70;

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
