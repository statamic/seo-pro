<?php

namespace Statamic\SeoPro\DeadLinks\Stache;

use Illuminate\Support\Collection;
use Statamic\Fields\Blueprint;
use Statamic\SeoPro\DeadLinks\Link;
use Statamic\SeoPro\DeadLinks\LinkBlueprint;
use Statamic\SeoPro\DeadLinks\LinkQueryBuilder;
use Statamic\SeoPro\DeadLinks\LinkRepository as RepositoryContract;
use Statamic\Stache\Stache;

class LinkRepository implements RepositoryContract
{
    protected $stache;
    protected $store;

    public function __construct(Stache $stache)
    {
        $this->stache = $stache;
        $this->store = $stache->store('seo_pro_dead_links');
    }

    public function all(): Collection
    {
        return $this->query()->get();
    }

    public function query(): LinkQueryBuilder
    {
        return app(LinkQueryBuilder::class);
    }

    public function find($id): ?Link
    {
        return $this->query()->where('id', $id)->first();
    }

    public function findByUrl(string $url, string $site): ?Link
    {
        return $this->query()->where('site', $site)->where('url', $url)->first();
    }

    public function make(): Link
    {
        return app(Link::class);
    }

    public function save(Link $link): void
    {
        if (! $link->id()) {
            $link->id($this->stache->generateId());
        }

        $this->store->save($link);
    }

    public function delete(Link $link): void
    {
        $this->store->delete($link);
    }

    public function blueprint(): Blueprint
    {
        return (new LinkBlueprint)();
    }

    public static function bindings(): array
    {
        return [
            LinkQueryBuilder::class => \Statamic\SeoPro\DeadLinks\Stache\LinkQueryBuilder::class,
        ];
    }
}
