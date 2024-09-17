<?php

namespace Statamic\SeoPro\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\SeoPro\Contracts\TextProcessing\Embeddings\EntryEmbeddingsRepository;
use Statamic\SeoPro\Contracts\TextProcessing\Keywords\KeywordsRepository;
use Statamic\SeoPro\Contracts\TextProcessing\Links\LinkCrawler;

class StartTheEnginesCommand extends Command
{
    use RunsInPlease;

    protected $signature = 'statamic:seo-pro:vroom';

    protected $description = 'Starts the engines';

    public function handle(
        LinkCrawler $crawler,
        KeywordsRepository $keywordsRepository,
        EntryEmbeddingsRepository $entryEmbeddingsRepository,
    ) {
        $this->line('Getting things ready...');

        $crawler->scanAllEntries();
        $keywordsRepository->generateKeywordsForAllEntries();
        $entryEmbeddingsRepository->generateEmbeddingsForAllEntries();

        $this->info('Vroom vroom.');
    }
}
