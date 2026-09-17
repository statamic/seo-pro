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

        if ($this->length < config('statamic.seo-pro.reports.title_length.warn_min', 30)) {
            return __('seo-pro::messages.rules.title_length_page_failing_too_short');
        }

        if ($this->length > config('statamic.seo-pro.reports.title_length.pass_max', 60)) {
            return __('seo-pro::messages.rules.title_length_page_failing_too_long');
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
        if (! $data) {
            return;
        }

        $this->failures = $data['failures'] ?? 0;
        $this->warnings = $data['warnings'] ?? 0;
    }

    public function pageFailingComment()
    {
        if ($this->length === 0) {
            return '';
        }

        return $this->pageWarningComment();
    }

    public function pagePassingComment()
    {
        return __('seo-pro::messages.rules.title_length_page_passing', ['length' => $this->length]);
    }

    public function pageWarningComment()
    {
        return __('seo-pro::messages.rules.title_length_page_warning', [
            'length' => $this->length,
            'min' => config('statamic.seo-pro.reports.title_length.warn_min', 30),
            'max' => config('statamic.seo-pro.reports.title_length.pass_max', 60),
        ]);
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
        $this->length = mb_strlen($this->page->get('title') ?? '');
    }

    public function pageStatus()
    {
        $warnMin = config('statamic.seo-pro.reports.title_length.warn_min', 30);
        $passMax = config('statamic.seo-pro.reports.title_length.pass_max', 60);
        $warnMax = config('statamic.seo-pro.reports.title_length.warn_max', 70);

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
