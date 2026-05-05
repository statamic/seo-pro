<?php

namespace Statamic\SeoPro\Events;

use Statamic\Contracts\Git\ProvidesCommitMessage;
use Statamic\Events\Event;
use Statamic\SeoPro\Redirects\Redirect;

class RedirectSaved extends Event implements ProvidesCommitMessage
{
    public function __construct(public Redirect $redirect) {}

    public function commitMessage(): string
    {
        return __('seo-pro::messages.redirect_saved', [], config('statamic.git.locale'));
    }
}
