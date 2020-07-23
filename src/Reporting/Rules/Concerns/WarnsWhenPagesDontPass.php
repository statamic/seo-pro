<?php

namespace Statamic\SeoPro\Reporting\Rules\Concerns;

use Statamic\SeoPro\Reporting\Page;

trait WarnsWhenPagesDontPass
{
    use FailsWhenPagesDontPass;

    public function siteStatus()
    {
        return $this->failures === 0 ? 'pass' : 'warning';
    }
}
