<?php

namespace Statamic\SeoPro\Actions;

use Statamic\Actions\Action;
use Statamic\SeoPro\DeadLinks\Link;
use Statamic\SeoPro\Jobs\CheckDeadLinksJob;

class RecheckLink extends Action
{
    protected $icon = 'sync';
    protected $confirm = false;

    public function visibleTo($item): bool
    {
        return $item instanceof Link;
    }

    public function authorize($user, $item): bool
    {
        return $user->can('manage seo dead links');
    }

    public function buttonText()
    {
        /** @translation */
        return 'Recheck Link|Recheck :count Links';
    }

    public function run($items, $values)
    {
        CheckDeadLinksJob::dispatch($items->map->id()->all());

        return trans_choice('Link queued for rechecking|Links queued for rechecking', $items->count());
    }
}
