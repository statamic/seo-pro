<?php

namespace Statamic\SeoPro\TextProcessing\Embeddings;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Entry as EntryApi;
use Statamic\SeoPro\Contracts\TextProcessing\ConfigurationRepository;
use Statamic\SeoPro\Contracts\TextProcessing\Content\ContentRetriever;
use Statamic\SeoPro\Contracts\TextProcessing\Content\Tokenizer;
use Statamic\SeoPro\Contracts\TextProcessing\Embeddings\EntryEmbeddingsRepository;
use Statamic\SeoPro\Contracts\TextProcessing\Embeddings\Extractor;
use Statamic\SeoPro\TextProcessing\Concerns\ChecksForContentChanges;
use Statamic\SeoPro\TextProcessing\EntryQuery;
use Statamic\SeoPro\TextProcessing\Models\EntryEmbedding;
use Statamic\SeoPro\TextProcessing\Models\EntryLink;
use Statamic\SeoPro\TextProcessing\Similarity\ResolverOptions;
use Statamic\SeoPro\TextProcessing\Vectors\Vector;

class EmbeddingsRepository implements EntryEmbeddingsRepository
{
    use ChecksForContentChanges;

    /** @var array<string, EntryEmbedding> */
    protected array $embeddingInstanceCache = [];

    protected string $configurationHash;

    public function __construct(
        protected readonly Extractor $embeddingsExtractor,
        protected readonly ContentRetriever $contentRetriever,
        protected readonly ConfigurationRepository $configurationRepository,
    ) {
        $this->configurationHash = self::getConfigurationHash();
    }

    protected function relatedEmbeddingsQuery(Entry $entry, ResolverOptions $options): Builder
    {
        $site = $entry->site()?->handle() ?? 'default';
        $entryLink = EntryLink::where('entry_id', $entry->id())->first();

        $collection = $entry->collection()->handle();
        $collectionConfig = $this->configurationRepository->getCollectionConfiguration($collection);

        $disabledCollections = $this->configurationRepository->getDisabledCollections();

        $ignoredEntries = $entryLink?->ignored_entries ?? [];
        $ignoredEntries[] = $entry->id();

        $query = EntryEmbedding::query()
            ->whereHas('entryLink', function (Builder $query) use ($entry, $options) {
                $query = $query->where('can_be_suggested', true);

                if ($options->preventCircularLinks) {
                    $query = $query->whereJsonDoesntContain('normalized_internal_links', $entry->uri);
                }

                return $query;
            })
            ->whereNotIn('entry_id', $ignoredEntries)
            ->whereNotIn('collection', $disabledCollections);

        if (! $collectionConfig->allowLinkingAcrossSites) {
            $query->where('site', $site);
        }

        if (! $collectionConfig->allowLinkingToAllCollections) {
            $query->whereIn('collection', $collectionConfig->linkableCollections);
        }

        return $query;
    }

    public static function getConfigurationHash(): string
    {
        return sha1(implode('', [
            'embeddings',
            get_class(app(Tokenizer::class)),
            (string) config('statamic.seo-pro.text_analysis.openai.token_limit', 8000),
            config('statamic.seo-pro.text_analysis.openai.model', 'text-embeddings-3-small'),
        ]));
    }

    public function getRelatedEmbeddingsForEntryLazy(Entry $entry, ResolverOptions $options)
    {
        /** @var EntryEmbedding $embedding */
        foreach ($this->relatedEmbeddingsQuery($entry, $options)->lazy(200) as $embedding) {
            yield $this->makeVector(
                $embedding->entry_id,
                null,
                $embedding
            );
        }
    }

    public function getRelatedEmbeddingsForEntry(Entry $entry, ResolverOptions $options): Collection
    {
        return $this->makeVectorCollection(
            $this->relatedEmbeddingsQuery($entry, $options)->get()
        );
    }

    public function generateEmbeddingsForAllEntries(): void
    {
        EntryQuery::query()->chunk(100, function ($entries) {
            $entryIds = $entries->pluck('id')->all();
            $this->fillEmbeddingInstanceCache($entryIds);

            /** @var array<string, EntryLink> $entryLinks */
            $entryLinks = EntryLink::whereIn('entry_id', $entryIds)->get()->keyBy('entry_id')->all();

            foreach ($entries as $entry) {
                $entryId = $entry->id();

                if (
                    array_key_exists($entryId, $this->embeddingInstanceCache) &&
                    array_key_exists($entryId, $entryLinks) &&
                    $entryLinks[$entryId]->content_hash === $this->embeddingInstanceCache[$entryId]->content_hash &&
                    $this->configurationHash === $this->embeddingInstanceCache[$entryId]->configuration_hash
                ) {
                    continue;
                }

                $this->generateEmbeddingsForEntry($entry);
            }

            unset($entryLinks);
            $this->clearEmbeddingInstanceCache();
        });
    }

    protected function fillEmbeddingInstanceCache(array $entryIds): void
    {
        $this->embeddingInstanceCache = EntryEmbedding::whereIn('entry_id', $entryIds)->get()->keyBy('entry_id')->all();
    }

    protected function clearEmbeddingInstanceCache(): void
    {
        $this->embeddingInstanceCache = [];
    }

    protected function getEntryEmbedding(string $entryId): EntryEmbedding
    {
        if (array_key_exists($entryId, $this->embeddingInstanceCache)) {
            return $this->embeddingInstanceCache[$entryId];
        }

        return EntryEmbedding::firstOrNew(['entry_id' => $entryId]);
    }

    public function generateEmbeddingsForEntry(Entry $entry): void
    {
        $id = $entry->id();

        $embedding = $this->getEntryEmbedding($id);

        $content = $this->contentRetriever->getContent($entry, false);

        if ($this->isContentSame($embedding, $content) && $embedding->configuration_hash === $this->configurationHash) {
            return;
        }

        $contentHash = $this->contentRetriever->hashContent($content);

        $content = $this->contentRetriever->stripTags($content);

        $collection = $entry->collection()->handle();
        $site = $entry->site()->handle();
        $blueprint = $entry->blueprint()->handle();

        $embedding->collection = $collection;
        $embedding->site = $site;
        $embedding->blueprint = $blueprint;
        $embedding->content_hash = $contentHash;
        $embedding->configuration_hash = $this->configurationHash;

        try {
            $embedding->embedding = array_values(
                $this->embeddingsExtractor->transform($content) ?? []
            );

            $embedding->saveQuietly();
        } catch (Exception $exception) {
            Log::error($exception);
        }
    }

    public function getEmbeddingsForEntry(Entry $entry): ?Vector
    {
        return $this->makeVector(
            $entry->id(),
            $entry,
            EntryEmbedding::query()->where('entry_id', $entry->id())->first()
        );
    }

    protected function makeVector(string $id, ?Entry $entry, ?EntryEmbedding $embedding): ?Vector
    {
        $entryVector = new Vector;
        $entryVector->id($id);
        $entryVector->entry($entry);
        $entryVector->vector($embedding?->embedding ?? []);

        return $entryVector;
    }

    protected function makeVectorCollection(Collection $vectors, bool $withEntries = false): Collection
    {
        $vectors = $vectors->keyBy('entry_id');
        $entryIds = $vectors->keys()->all();
        $entries = collect();

        if ($withEntries) {
            $entries = EntryApi::query()->whereIn('id', $entryIds)->get()->keyBy('id');
        }

        $results = [];

        foreach ($entryIds as $id) {
            $results[] = $this->makeVector(
                $id,
                $entries[$id] ?? null,
                $vectors[$id] ?? null,
            );
        }

        return collect($results);
    }

    public function getEmbeddingsForCollection(string $handle, string $site = 'default'): Collection
    {
        return $this->makeVectorCollection(
            EntryEmbedding::query()->where('collection', $handle)->where('site', $site)->get()
        );
    }

    public function getEmbeddingsForSite(string $handle): Collection
    {
        return $this->makeVectorCollection(
            EntryEmbedding::query()->where('site', $handle)->get()
        );
    }

    public function deleteEmbeddingsForEntry(string $entryId): void
    {
        EntryEmbedding::where('entry_id', $entryId)->delete();
    }

    public function deleteEmbeddingsForCollection(string $handle): void
    {
        EntryEmbedding::where('collection', $handle)->delete();
    }

    public function deleteEmbeddingsForSite(string $handle): void
    {
        EntryEmbedding::where('site', $handle)->delete();
    }
}
