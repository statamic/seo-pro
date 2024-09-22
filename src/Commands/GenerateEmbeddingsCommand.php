<?php

namespace Statamic\SeoPro\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\SeoPro\Contracts\TextProcessing\Embeddings\EntryEmbeddingsRepository;

class GenerateEmbeddingsCommand extends Command
{
    use RunsInPlease;

    protected $signature = 'statamic:seo-pro:generate-embeddings';

    protected $description = 'Generates embeddings for entries.';

    public function handle(EntryEmbeddingsRepository $vectors)
    {
        $this->line('Generating...');

        $vectors->generateEmbeddingsForAllEntries();

        $this->info('Embeddings generated.');
    }
}
