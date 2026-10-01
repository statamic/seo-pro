<?php

namespace Statamic\SeoPro\Actions;

use Statamic\Actions\Action;
use Statamic\SeoPro\BrokenLinks\ExternalLink;
use Statamic\SeoPro\Jobs\CheckExternalLinksJob;

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
        CheckExternalLinksJob::dispatch($items->map->id()->all());

        return trans_choice('Link queued for rechecking|Links queued for rechecking', $items->count());
    }
}
