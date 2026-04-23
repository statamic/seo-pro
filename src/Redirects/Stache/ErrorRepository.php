<?php

namespace Statamic\SeoPro\Redirects\Stache;

use Illuminate\Support\Collection;
use Statamic\Facades\Site;
use Statamic\Fields\Blueprint;
use Statamic\SeoPro\Redirects\Error;
use Statamic\SeoPro\Redirects\ErrorBlueprint;
use Statamic\SeoPro\Redirects\ErrorQueryBuilder;
use Statamic\SeoPro\Redirects\ErrorRepository as RepositoryContract;
use Statamic\Stache\Stache;
use Statamic\Support\Str;

class ErrorRepository implements RepositoryContract
{
    protected $stache;
    protected $store;

    public function __construct(Stache $stache)
    {
        $this->stache = $stache;
        $this->store = $stache->store('errors');
    }

    public function all(): Collection
    {
        return $this->query()->get();
    }

    public function query(): ErrorQueryBuilder
    {
        return app(ErrorQueryBuilder::class);
    }

    public function find($id): ?Error
    {
        return $this->query()->where('id', $id)->first();
    }

    public function make(): Error
    {
        return app(Error::class);
    }

    public function save(Error $error): void
    {
        if (! $error->site()) {
            $error->site(Site::default()->handle());
        }

        if (! $error->id()) {
            $slug = Str::slug($error->url());
            $id = $slug;
            $suffix = 1;

            while ($this->query()->where('id', $id)->where('site', $error->site())->first()) {
                $id = $slug.'-'.$suffix++;
            }

            $error->id($id);
        }

        $this->store->save($error);
    }

    public function delete(Error $error): void
    {
        $this->store->delete($error);
    }

    public function blueprint(): Blueprint
    {
        return (new ErrorBlueprint)();
    }

    public static function bindings(): array
    {
        return [
            ErrorQueryBuilder::class => \Statamic\SeoPro\Redirects\Stache\ErrorQueryBuilder::class,
        ];
    }
}
