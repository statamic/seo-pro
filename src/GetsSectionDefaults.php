<?php

namespace Statamic\SeoPro;

use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Blink;
use Statamic\Facades\Blueprint;
use Statamic\Taxonomies\LocalizedTerm;

trait GetsSectionDefaults
{
    public function getSectionDefaults($current)
    {
        if (! $parent = $this->getSectionParent($current)) {
            return [];
        }

        return Blink::once($this->getCacheKey($parent), function () use ($parent) {
            return $parent->cascade('seo');
        });
    }

    public function getAugmentedSectionDefaults($current)
    {
        if (! $parent = $this->getSectionParent($current)) {
            return [];
        }

        return Blink::once($this->getCacheKey($parent).'.augmented', function () use ($parent) {
            return Blueprint::make()
                ->setContents([
                    'tabs' => [
                        'main' => [
                            'sections' => Fields::new()->getConfig(),
                        ],
                    ],
                ])
                ->fields()
                ->addValues($seo = $parent->cascade('seo') ?: [])
                ->augment()
                ->values()
                ->only(array_keys($seo));
        });
    }

    protected function getCacheKey($parent)
    {
        return 'seo-pro.section-defaults.'.get_class($parent).'::'.$parent->handle();
    }

    protected function getSectionParent($current)
    {
        if ($current instanceof Entry) {
            return $current->collection();
        } elseif ($current instanceof Term || $current instanceof LocalizedTerm) {
            return $current->taxonomy();
        }

        return null;
    }
}
