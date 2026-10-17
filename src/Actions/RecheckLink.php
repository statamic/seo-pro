<?php

namespace Statamic\SeoPro\Actions;

use Statamic\Actions\Action;
use Statamic\SeoPro\BrokenLinks\CheckExternalLinks;
use Statamic\SeoPro\BrokenLinks\ExternalLink;

class RecheckLink extends Action
{
    protected $icon = 'sync';
    protected $confirm = false;

    public function visibleTo($item): bool
    {
        return $item instanceof ExternalLink;
    }

    public function authorize($user, $item): bool
    {
        return $user->can('view seo broken links');
    }

    public function buttonText()
    {
        /** @translation */
        return 'Recheck Link|Recheck :count Links';
    }

    public function run($items, $values)
    {
        CheckExternalLinks::dispatch($items->map->id()->all());

        return trans_choice('Link queued for rechecking|Links queued for rechecking', $items->count());
    }
}
