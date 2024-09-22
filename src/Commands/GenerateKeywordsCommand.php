<?php

namespace Statamic\SeoPro\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\SeoPro\Contracts\TextProcessing\Keywords\KeywordsRepository;

class GenerateKeywordsCommand extends Command
{
    use RunsInPlease;

    protected $signature = 'statamic:seo-pro:generate-keywords';

    protected $description = 'Generates keywords for entries.';

    public function handle(KeywordsRepository $keywords)
    {
        $this->line('Generating...');

        $keywords->generateKeywordsForAllEntries();

        $this->info('Keywords generated.');
    }
}
