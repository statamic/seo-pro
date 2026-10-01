<?php

namespace Statamic\SeoPro\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\SeoPro\BrokenLinks\LinkChecker;

class CheckBrokenLinksCommand extends Command
{
    use RunsInPlease;

    protected $signature = 'statamic:seo-pro:check-broken-links';

    protected $description = 'Check any external links that are due for a recheck';

    public function handle(): int
    {
        if (! config('statamic.seo-pro.broken_links.enabled', true)) {
            $this->components->info('Broken link checking is disabled in config, skipping.');

            return self::SUCCESS;
        }

        $checked = LinkChecker::checkDue();

        $this->components->info("Checked {$checked} link(s).");

        return self::SUCCESS;
    }
}
