<?php

namespace Statamic\SeoPro\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\SeoPro\Contracts\TextProcessing\Links\LinkCrawler;

class ScanLinksCommand extends Command
{
    use RunsInPlease;

    protected $signature = 'statamic:seo-pro:scan-links';

    protected $description = 'Scans entries to find links.';

    public function handle(LinkCrawler $crawler)
    {
        $this->line('Scanning links...');

        $crawler->scanAllEntries();

        $this->info('Links scanned.');
    }
}
