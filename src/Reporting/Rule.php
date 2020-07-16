<?php

namespace Statamic\Addons\SeoPro\Reporting;

abstract class Rule
{
    protected $page;
    protected $report;
    protected $validatesPages = true;

    public function id()
    {
        return class_basename(static::class);
    }

    public function setReport(Report $report)
    {
        $this->report = $report;

        return $this;
    }

    public function setPage(Page $page)
    {
        $this->page = $page;

        return $this;
    }

    public function validatesPages()
    {
        return $this->validatesPages;
    }

    public function isValidatingPage()
    {
        return $this->page !== null;
    }

    public function comment()
    {
        switch ($this->status()) {
            case 'pass':
                return $this->isValidatingPage() ? $this->pagePassingComment() : $this->sitePassingComment();
            case 'fail':
                return $this->isValidatingPage() ? $this->pageFailingComment() : $this->siteFailingComment();
            case 'warning':
                return $this->isValidatingPage() ? $this->pageWarningComment() : $this->siteWarningComment();
        }
    }

    public function passingComment()
    {
        return '';
    }

    public function failingComment()
    {
        return '';
    }

    public function warningComment()
    {
        return '';
    }

    public function pagePassingComment()
    {
        return $this->passingComment();
    }

    public function pageFailingComment()
    {
        return $this->failingComment();
    }

    public function pageWarningComment()
    {
        return $this->warningComment();
    }

    public function sitePassingComment()
    {
        return $this->passingComment();
    }

    public function siteFailingComment()
    {
        return $this->failingComment();
    }

    public function siteWarningComment()
    {
        return $this->warningComment();
    }

    public function process()
    {
        return $this->isValidatingPage() ? $this->processPage() : $this->processSite();
    }

    public function processPage()
    {
        //
    }

    public function processSite()
    {
        //
    }

    public function save()
    {
        return $this->isValidatingPage() ? $this->savePage() : $this->saveSite();
    }

    public function saveSite()
    {
        //
    }

    public function savePage()
    {
        //
    }

    public function load($data)
    {
        return $this->isValidatingPage() ? $this->loadPage($data) : $this->loadSite($data);
    }

    public function loadSite($data)
    {
        //
    }

    public function loadPage($data)
    {
        //
    }

    public function description()
    {
        return $this->isValidatingPage() ? $this->pageDescription() : $this->siteDescription();
    }

    public function status()
    {
        return $this->isValidatingPage() ? $this->pageStatus() : $this->siteStatus();
    }

    public function siteStatus()
    {
        //
    }

    public function pageStatus()
    {
        //
    }

    public function maxPoints()
    {
        return 0;
    }

    public function demerits()
    {
        return 0;
    }
}
