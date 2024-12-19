<?php

namespace Statamic\SeoPro\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\SeoPro\Contracts\Linking\Embeddings\EntryEmbeddingsRepository;
use Statamic\SeoPro\Contracts\Linking\Keywords\KeywordsRepository;
use Statamic\SeoPro\Contracts\Linking\Links\LinkCrawler;

class AnalyzeContentCommand extends Command
{
    use RunsInPlease;

    protected $signature = 'statamic:seo-pro:analyze-content';

    protected $description = 'Crawls all content to gather links and generate keywords and embeddings.';

    public function handle(
        LinkCrawler $crawler,
        KeywordsRepository $keywordsRepository,
        EntryEmbeddingsRepository $entryEmbeddingsRepository,
    ) {
        $this->line('Analyzing content...');

        $crawler->scanAllEntries();
        $keywordsRepository->generateKeywordsForAllEntries();
        $entryEmbeddingsRepository->generateEmbeddingsForAllEntries();

        $this->info('Content analyzed.');
    }
}
