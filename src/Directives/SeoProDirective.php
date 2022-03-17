<?php

namespace Statamic\SeoPro\Directives;

use Statamic\SeoPro\Tags\SeoProTags;

class SeoProDirective extends SeoProTags
{
    public function renderTag($tag, $context)
    {
        return $this->setContext($context)->$tag();
    }
}
