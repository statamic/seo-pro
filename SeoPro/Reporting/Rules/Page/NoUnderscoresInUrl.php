<?php

namespace Statamic\Addons\SeoPro\Reporting\Rules\Page;

use Statamic\API\Str;
use Statamic\Addons\SeoPro\Reporting\Page;

class NoUnderscoresInUrl extends Rule
{
    public function description()
    {
        return 'The URL should not contain underscores.';
    }

    public function process()
    {
        $this->passes = !Str::contains($this->page->url(), '_');
    }

    public function passes()
    {
        return $this->passes;
    }

    public function save()
    {
        return $this->passes;
    }

    public function load($saved)
    {
        $this->passes = $saved;
    }
}
