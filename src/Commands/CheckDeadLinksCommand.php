<?php

namespace Statamic\SeoPro\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\SeoPro\DeadLinks\LinkChecker;

class CheckDeadLinksCommand extends Command
{
    use RunsInPlease;

    protected $signature = 'statamic:seo-pro:check-dead-links';

    protected $description = 'Check any dead links that are due for a recheck, and email a digest of failures if enabled';

    public function handle(): int
    {
        if (! config('statamic.seo-pro.dead_links.enabled', true)) {
            $this->components->info('Dead link checking is disabled in config, skipping.');

            return self::SUCCESS;
        }

        $checked = LinkChecker::checkDue();

        $this->components->info("Checked {$checked} link(s).");

        return self::SUCCESS;
    }
}
