<?php

namespace Statamic\Addons\SeoPro\Reporting\Rules\Page;

use Statamic\Addons\SeoPro\Reporting\Page;

class UniqueMetaDescription extends Rule
{
    public function description()
    {
        return 'The meta description should be unique.';
    }

    protected function failingComment()
    {
        return sprintf('Found %s pages with the same meta description.', $this->count);
    }

    public function process()
    {
        $this->count = $this->page->report()->pages()->filter(function ($page) {
            return $page->get('description') === $this->page->get('description');
        })->count();
    }

    public function status()
    {
        return $this->count === 1 ? 'pass' : 'fail';
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
