<?php

namespace Statamic\SeoPro;

use Statamic\Contracts\Entries\Entry;
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

        return $parent->cascade('seo');
    }
}
