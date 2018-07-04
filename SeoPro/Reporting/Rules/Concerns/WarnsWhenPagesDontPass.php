<?php

namespace Statamic\Addons\SeoPro\Reporting\Rules\Concerns;

use Statamic\Addons\SeoPro\Reporting\Page;

trait WarnsWhenPagesDontPass
{
    use FailsWhenPagesDontPass;

    public function siteStatus()
    {
        return $this->failures === 0 ? 'pass' : 'warning';
    }
}
