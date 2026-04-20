<?php

namespace Statamic\SeoPro\Redirects\Stache;

use Statamic\Fields\Blueprint;
use Statamic\SeoPro\Redirects\Blueprint as RedirectBlueprint;
use Statamic\SeoPro\Redirects\Redirect;
use Statamic\SeoPro\Redirects\RedirectQueryBuilder;
use Statamic\SeoPro\Redirects\RedirectRepository as RepositoryContract;
use Statamic\Stache\Stache;
use Statamic\Support\Str;

class RedirectRepository implements RepositoryContract
{
    protected $stache;
    protected $store;

    public function __construct(Stache $stache)
    {
        $this->stache = $stache;
        $this->store = $stache->store('redirects');
    }

    public function all()
    {
        return $this->query()->get();
    }

    public function query()
    {
        return app(RedirectQueryBuilder::class);
    }

    public function find($id): ?Redirect
    {
        return $this->query()->where('id', $id)->first();
    }

    public function make(): Redirect
    {
        return app(Redirect::class);
    }

    public function save(Redirect $redirect): void
    {
        if (! $redirect->id()) {
            $redirect->id(Str::slug($redirect->sourceUrl()).'-'.Str::random(5));
        }

        $this->store->save($redirect);
    }

    public function blueprint(): Blueprint
    {
        return (new RedirectBlueprint)();
    }

    public function delete(Redirect $redirect): void
    {
        $this->store->delete($redirect);
    }

    public static function bindings(): array
    {
        return [
            RedirectQueryBuilder::class => \Statamic\SeoPro\Redirects\Stache\RedirectQueryBuilder::class,
        ];
    }
}
