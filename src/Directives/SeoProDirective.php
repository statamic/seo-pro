<?php

namespace Statamic\SeoPro\Directives;

use Facades\Statamic\View\Cascade;
use Statamic\SeoPro\Tags\SeoProTags;

class SeoProDirective extends SeoProTags
{
    public function renderTag($tag, $context)
    {
        if ($this->isMissingContext($context)) {
            $context = $this->getContextFromCascade();
        }

        return $this->setContext($context)->$tag();
    }

    protected function isMissingContext($context)
    {
        return ! isset($context['current_template']);
    }

    protected function getContextFromCascade()
    {
        return Cascade::instance()->toArray();
    }
}
