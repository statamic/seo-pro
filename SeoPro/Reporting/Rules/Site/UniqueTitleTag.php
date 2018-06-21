<?php

namespace Statamic\Addons\SeoPro\Reporting\Rules\Site;

class UniqueTitleTag extends NoFailuresRule
{
    public function description()
    {
        return 'Each page should have a unique title tag.';
    }

    public function failingComment()
    {
        return sprintf(
            '%s pages with duplicate title tags.',
            $this->count
        );
    }

    public function status()
    {
        return $this->count === 0 ? 'pass' : 'warning';
    }
}
