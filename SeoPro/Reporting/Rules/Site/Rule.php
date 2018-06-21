<?php

namespace Statamic\Addons\SeoPro\Reporting\Rules\Site;

use Statamic\Addons\SeoPro\Reporting\Page;
use Statamic\Addons\SeoPro\Reporting\Report;
use Statamic\Addons\SeoPro\Reporting\Rules\Rule as AbstractRule;

class Rule extends AbstractRule
{
    protected $report;

    public function setReport(Report $report)
    {
        $this->report = $report;

        return $this;
    }

    protected function passesPageRule(Page $page, $id)
    {
        $class = "Statamic\\Addons\\SeoPro\\Reporting\\Rules\\Page\\$id";

        $rule = new $class;

        $rule->load($page->results()[$id]);

        return $rule->passes();
    }

    protected function failsPageRule(Page $page, $id)
    {
        return ! $this->passesPageRule($page, $id);
    }
}
