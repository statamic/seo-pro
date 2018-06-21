<?php

namespace Statamic\Addons\SeoPro\Reporting\Rules\Site;

class UniqueMetaDescription extends NoFailuresRule
{
    public function description()
    {
        return 'Each page should have a unique meta description.';
    }

    public function failingComment()
    {
        return sprintf(
            '%s pages with duplicate meta descriptions.',
            $this->count
        );
    }
}
