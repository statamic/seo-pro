<?php

namespace Statamic\SeoPro\Events;

use Statamic\Contracts\Git\ProvidesCommitMessage;
use Statamic\Events\Event;

class SeoProSiteDefaultsSaved extends Event implements ProvidesCommitMessage
{
    public $defaults;

    public function __construct($defaults)
    {
        $this->defaults = $defaults;
    }

    public function commitMessage()
    {
        return __('SEO Pro site defaults saved', [], config('statamic.git.locale'));
    }
}
