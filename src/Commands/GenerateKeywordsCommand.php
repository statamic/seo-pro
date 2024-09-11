<?php

namespace Statamic\SeoPro\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\SeoPro\Contracts\TextProcessing\Keywords\KeywordsRepository;

class GenerateKeywordsCommand extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:seo-pro:generate-keywords';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates keywords for entries.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(KeywordsRepository $keywords)
    {
        $this->line('Generating...');

        $keywords->generateKeywordsForAllEntries();

        $this->info('Keywords generated.');
    }
}