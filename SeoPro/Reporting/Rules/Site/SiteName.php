<?php

namespace Statamic\Addons\SeoPro\Reporting\Rules\Site;

use Statamic\Addons\SeoPro\Reporting\Rules\Site\Rule;
use Statamic\Addons\SeoPro\Settings;
use Statamic\Addons\SeoPro\TagData;

class SiteName extends Rule
{
    public function description()
    {
        return 'The site should have a name.';
    }

    public function passingComment()
    {
        return $this->report->defaults()->get('site_name');
    }

    public function process()
    {
        $this->passes = $this->report->defaults()->has('site_name');
    }

    public function status()
    {
        return $this->passes ? 'pass' : 'fail';
    }

    public function save()
    {
        return $this->passes;
    }

    public function load($data)
    {
        $this->passes = $data;
    }
}
