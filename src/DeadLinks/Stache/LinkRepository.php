<?php

namespace Statamic\SeoPro\DeadLinks\Stache;

use Illuminate\Support\Collection;
use Statamic\Fields\Blueprint;
use Statamic\SeoPro\DeadLinks\Link;
use Statamic\SeoPro\DeadLinks\LinkBlueprint;
use Statamic\SeoPro\DeadLinks\LinkQueryBuilder;
use Statamic\SeoPro\DeadLinks\LinkRepository as RepositoryContract;
use Statamic\Stache\Stache;
use Statamic\Support\Str;

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

    public function make(): Link
    {
        return app(Link::class);
    }

    public function save(Link $link): void
    {
        if (! $link->id()) {
            $id = $slug = $this->generateId($link->url());
            $suffix = 1;

            while ($this->query()->where('id', $id)->first()) {
                $id = $slug.'-'.$suffix++;
            }

            $link->id($id);
        }

        $this->store->save($link);
    }

    public function delete(Link $link): void
    {
        $this->store->delete($link);
    }

    private function generateId(string $url): string
    {
        if ($slug = Str::slug($url)) {
            return $slug;
        }

        return $this->stache->generateId();
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
