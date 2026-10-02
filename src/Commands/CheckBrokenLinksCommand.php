<?php

namespace Statamic\SeoPro\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Statamic\Console\RunsInPlease;
use Statamic\SeoPro\BrokenLinks\ExternalLink;
use Statamic\SeoPro\BrokenLinks\LinkChecker;
use Statamic\SeoPro\Facades;
use Statamic\SeoPro\Notifications\BrokenLinksDigest;

class CheckBrokenLinksCommand extends Command
{
    use RunsInPlease;

    protected $signature = 'statamic:seo-pro:check-broken-links';

    protected $description = 'Check any external links that are due for a recheck';

    public function handle(LinkChecker $checker): int
    {
        if (! config('statamic.seo-pro.broken_links.enabled', false)) {
            $this->components->error('Broken link checking is disabled.');

            return self::FAILURE;
        }

        $this->checkLinks();
        $this->sendDigest();

        $this->components->info("Checked {$links->count()} link(s).");

        return self::SUCCESS;
    }

    private function checkLinks(): void
    {
        $links = Facades\ExternalLink::query()
            ->where('next_check_at', '<=', now())
            ->orderBy('next_check_at')
            ->limit(config('statamic.seo-pro.broken_links.check.batch_size', 100))
            ->get();

        $checker->check($links);
    }

    private function sendDigest(): void
    {
        $recipients = config('statamic.seo-pro.broken_links.notifications.recipients', []);

        if (empty($recipients)) {
            return;
        }

        $links = Facades\ExternalLink::query()
            ->where('broken_since', '<=', now()->subDay())
            ->whereNull('notified_at')
            ->get();

        if ($links->isEmpty()) {
            return;
        }

        Notification::route('mail', $recipients)->notify(new BrokenLinksDigest($links));

        $links->each(fn (ExternalLink $link): bool => $link->notifiedAt(now())->save());
    }
}
