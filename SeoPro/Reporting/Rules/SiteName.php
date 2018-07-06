<?php

namespace Statamic\Addons\SeoPro\Reporting\Rules;

use Statamic\Addons\SeoPro\Reporting\Rule;

class SiteName extends Rule
{
    protected $validatesPages = false;
    protected $passes;

    public function description()
    {
        return 'A site name must be defined.';
    }

    public function process()
    {
        $this->passes = ! empty(trim($this->siteName()));
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

    public function comment()
    {
        return $this->siteName();
    }

    protected function siteName()
    {
        return $this->report->defaults()->get('site_name');
    }

    public function maxPoints()
    {
        return 10;
    }

    public function demerits()
    {
        if (! $this->passes) {
            return $this->maxPoints();
        }
    }
}
