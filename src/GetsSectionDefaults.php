<?php

namespace Statamic\SeoPro;

use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Blueprint;
use Statamic\Taxonomies\LocalizedTerm;

trait GetsSectionDefaults
{
    public function getSectionDefaults($current)
    {
        if ($current instanceof Entry) {
            $parent = $current->collection();
        } elseif ($current instanceof LocalizedTerm) {
            $parent = $current->taxonomy();
        } else {
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
}
