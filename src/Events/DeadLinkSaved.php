<?php

namespace Statamic\SeoPro\Events;

use Statamic\Contracts\Git\ProvidesCommitMessage;
use Statamic\Events\Event;
use Statamic\SeoPro\DeadLinks\Link;

class DeadLinkSaved extends Event implements ProvidesCommitMessage
{
    public function __construct(public Link $link) {}

    public function commitMessage(): string
    {
        return __('seo-pro::messages.dead_link_saved', [], config('statamic.git.locale'));
    }
}
