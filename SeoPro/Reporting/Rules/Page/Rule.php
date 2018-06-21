<?php

namespace Statamic\Addons\SeoPro\Reporting\Rules\Page;

use Statamic\Addons\SeoPro\Reporting\Page;
use Statamic\Addons\SeoPro\Reporting\Rules\Rule as AbstractRule;

class Rule extends AbstractRule
{
    protected $page;

    public function setPage(Page $page)
    {
        $this->page = $page;

        return $this;
    }
}
