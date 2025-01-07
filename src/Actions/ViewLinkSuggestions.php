<?php

namespace Statamic\SeoPro\Actions;

use Statamic\Actions\Action;
use Statamic\Contracts\Entries\Entry;

class ViewLinkSuggestions extends Action
{
    protected $confirm = false;

    public static function title()
    {
        return 'View Link Suggestions';
    }

    public function redirect($items, $values)
    {
        $id = $items->first()->id();

        return route('statamic.cp.seo-pro.internal-links.get-suggestions', $id);
    }

    public function visibleTo($item)
    {
        return $item instanceof Entry;
    }

    public function visibleToBulk($items)
    {
        return false;
    }
}
