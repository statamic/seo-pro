<?php

namespace Statamic\SeoPro\Directives;

use Facades\Statamic\View\Cascade;
use Statamic\SeoPro\Tags\SeoProTags;

class SeoProDirective extends SeoProTags
{
    public function renderTag($tag, $context)
    {
        if ($this->isMissingContext($context)) {
            $context = array_merge(
                $this->getContextFromCurrentRouteData(),
                $this->getContextFromCascade()
            );
        }

        return $this
            ->setContext($context)
            ->setParameters([])
            ->$tag();
    }

    protected function isMissingContext($context)
    {
        return ! isset($context['current_template']);
    }

    protected function getContextFromCascade()
    {
        $cascade = Cascade::instance();

        // If the cascade has not yet been hydrated, ensure it is hydrated.
        // This is important for people using custom route/controller/view implementations.
        if (empty($cascade->toArray())) {
            $cascade->hydrate();
        }

        return $cascade->toArray();
    }

    protected function getContextFromCurrentRouteData()
    {
        return app('router')->current()?->parameter('data') ?? [];
    }
}
