<?php

namespace Statamic\Addons\SeoPro\Reporting\Rules\Page;

use Statamic\Addons\SeoPro\Reporting\Page;

class UniqueTitleTag extends Rule
{
    protected $unique;

    public function description()
    {
        return 'The title tag should be unique.';
    }

    protected function failingComment()
    {
        return sprintf('Found %s pages with the same title tag.', $this->count);
    }

    public function process()
    {
        $this->count = $this->page->report()->pages()->filter(function ($page) {
            return $page->get('title') === $this->page->get('title');
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
