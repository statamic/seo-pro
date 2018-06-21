<?php

namespace Statamic\Addons\SeoPro\Reporting\Rules\Site;

class NoUnderscoresInUrl extends NoFailuresRule
{
    public function description()
    {
        return 'Page URLs should not contain underscores.';
    }

    public function failingComment()
    {
        return sprintf(
            '%s %s with underscores in their URLs.',
            $this->count,
            str_plural('page', $this->count)
        );
    }
}
