<?php

namespace Statamic\SeoPro\Commands;

use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\SeoPro\Contracts\TextProcessing\Embeddings\EntryEmbeddingsRepository;

class GenerateEmbeddingsCommand extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:seo-pro:generate-embeddings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates embeddings for entries.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(EntryEmbeddingsRepository $vectors)
    {
        $this->line('Generating...');

        $vectors->generateEmbeddingsForAllEntries();

        $this->info('Embeddings generated.');
    }
}
