<?php

namespace Statamic\SeoPro\BrokenLinks\Stache;

use Illuminate\Support\Collection;
use Statamic\Fields\Blueprint;
use Statamic\SeoPro\BrokenLinks\ExternalLink;
use Statamic\SeoPro\BrokenLinks\ExternalLinkBlueprint;
use Statamic\SeoPro\BrokenLinks\ExternalLinkQueryBuilder;
use Statamic\SeoPro\BrokenLinks\ExternalLinkRepository as RepositoryContract;
use Statamic\Stache\Stache;

class ExternalLinkRepository implements RepositoryContract
{
    protected $stache;
    protected $store;

    public function __construct(Stache $stache)
    {
        $this->stache = $stache;
        $this->store = $stache->store('seo_pro_external_links');
    }

    public function all(): Collection
    {
        return $this->query()->get();
    }

    public function query(): ExternalLinkQueryBuilder
    {
        return app(ExternalLinkQueryBuilder::class);
    }

    public function find($id): ?ExternalLink
    {
        return $this->query()->where('id', $id)->first();
    }

    public function findByUrl(string $url, string $site): ?ExternalLink
    {
        return $this->query()->where('site', $site)->where('url', $url)->first();
    }

    public function make(): ExternalLink
    {
        return app(ExternalLink::class);
    }

    public function save(ExternalLink $link): void
    {
        if (! $link->id()) {
            $link->id($this->stache->generateId());
        }

        $this->store->save($link);
    }

    public function delete(ExternalLink $link): void
    {
        $this->store->delete($link);
    }

    public function blueprint(): Blueprint
    {
        return (new ExternalLinkBlueprint)();
    }

    public static function bindings(): array
    {
        return [
            ExternalLinkQueryBuilder::class => \Statamic\SeoPro\BrokenLinks\Stache\ExternalLinkQueryBuilder::class,
        ];
    }
}
