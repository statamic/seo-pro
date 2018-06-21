<?php

namespace Statamic\Addons\SeoPro\Reporting\Rules\Site;

class ThreeSegmentUrls extends NoFailuresRule
{
    public function description()
    {
        return 'URLs should have a maximum of 3 segments.';
    }

    public function warningComment()
    {
        return sprintf(
            '%s %s with more than 3 segments in their URLs.',
            $this->count,
            str_plural('page', $this->count)
        );
    }

    public function status()
    {
        return $this->count === 0 ? 'pass' : 'warning';
    }
}
