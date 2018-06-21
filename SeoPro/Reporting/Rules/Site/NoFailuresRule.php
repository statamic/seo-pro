<?php

namespace Statamic\Addons\SeoPro\Reporting\Rules\Site;

use Statamic\Addons\SeoPro\Reporting\Page;

abstract class NoFailuresRule extends Rule
{
    protected function getPageRuleId()
    {
        return $this->id();
    }

    public function process()
    {
        $failures = $this->report->pages()->filter(function ($page) {
            return $this->failsPageRule($page, $this->getPageRuleId());
        });

        $this->count = $failures->count();
    }

    public function passes()
    {
        return $this->count === 0;
    }

    public function save()
    {
        return $this->count;
    }

    public function load($saved)
    {
        $this->count = $saved;
    }
}
