<?php

namespace Statamic\SeoPro;

use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Blueprint;
use Statamic\Taxonomies\LocalizedTerm;

trait GetsSectionDefaults
{
    public function getSectionDefaults($current)
    {
        if (! $parent = $this->getSectionParent($current)) {
            return [];
        }

        return $parent->cascade('seo');
    }

    public function getAugmentedSectionDefaults($current)
    {
        if (! $parent = $this->getSectionParent($current)) {
            return [];
        }

        return Blueprint::make()
            ->setContents([
                'fields' => Fields::new()->getConfig(),
            ])
            ->fields()
            ->addValues($parent->cascade('seo'))
            ->augment()
            ->values();
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
