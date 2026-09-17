<?php

namespace Statamic\SeoPro\Events;

use Illuminate\Support\Carbon;
use Statamic\Contracts\Git\ProvidesCommitMessage;
use Statamic\Events\Event;
use Statamic\SeoPro\Robots\RobotsPolicy;

class RobotsTxtGenerated extends Event implements ProvidesCommitMessage
{
    public function __construct(
        public RobotsPolicy $policy,
        public string $path,
        public Carbon $generatedAt,
    ) {}

    public function commitMessage(): string
    {
        return __('seo-pro::messages.robots_txt_generated', [], config('statamic.git.locale'));
    }
}
