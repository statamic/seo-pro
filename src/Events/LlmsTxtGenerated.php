<?php

namespace Statamic\SeoPro\Events;

use Illuminate\Support\Carbon;
use Statamic\Contracts\Git\ProvidesCommitMessage;
use Statamic\Events\Event;
use Statamic\SeoPro\Llms\LlmsDocument;
use Statamic\Sites\Site;

class LlmsTxtGenerated extends Event implements ProvidesCommitMessage
{
    public function __construct(
        public LlmsDocument $document,
        public Site $site,
        public string $path,
        public Carbon $generatedAt,
    ) {}

    public function commitMessage(): string
    {
        return __('seo-pro::messages.llms_txt_generated', [], config('statamic.git.locale'));
    }
}
