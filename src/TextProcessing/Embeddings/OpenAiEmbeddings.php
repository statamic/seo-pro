<?php

namespace Statamic\SeoPro\TextProcessing\Embeddings;

use OpenAI;
use Statamic\SeoPro\Contracts\Content\Tokenizer;
use Statamic\SeoPro\Contracts\TextProcessing\Embeddings\Extractor;

class OpenAiEmbeddings implements Extractor
{
    protected string $embeddingsModel = '';

    protected int $tokenLimit = 0;

    protected Tokenizer $tokenizer;

    public function __construct(Tokenizer $tokenizer)
    {
        $this->tokenizer = $tokenizer;
        $this->tokenLimit = config('statamic.seo-pro.linking.openai.token_limit', 8000);
        $this->embeddingsModel = config('statamic.seo-pro.linking.openai.model', 'text-embeddings-3-small');
    }

    protected function makeClient(): OpenAI\Client
    {
        return OpenAI::factory()
            ->withBaseUri(config('statamic.seo-pro.linking.openai.base_uri'))
            ->withApiKey(config('statamic.seo-pro.linking.openai.api_key'))
            ->make();
    }

    public function transform(string $content): array
    {
        $vector = [];

        foreach ($this->tokenizer->chunk($content, $this->tokenLimit) as $chunk) {
            $vector = array_merge($vector, $this->getEmbeddingFromApi($chunk));
        }

        return $vector;
    }

    protected function getEmbeddingFromApi(string $content): array
    {
        $response = $this->makeClient()->embeddings()->create([
            'model' => $this->embeddingsModel,
            'input' => $content,
        ]);

        $vector = [];

        foreach ($response->embeddings as $embedding) {
            $vector = array_merge($vector, $embedding->embedding);
        }

        return $vector;
    }
}
