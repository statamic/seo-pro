<?php

namespace Statamic\SeoPro\Directives;

use Statamic\Tags\Context;
use Statamic\SeoPro\GetsOutputHTML;

class SeoProDirective
{
    use GetsOutputHTML;

    protected $context;

    public function handle($tag, $context)
    {
        $this->context = new Context($context);
        return $this->$tag();
    }
}
