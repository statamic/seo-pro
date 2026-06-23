<?php

namespace Statamic\SeoPro\Redirects\Eloquent;

use Statamic\Facades\Site;
use Statamic\SeoPro\Redirects\Redirect;
use Statamic\SeoPro\Redirects\RedirectQueryBuilder;
use Statamic\SeoPro\Redirects\RedirectRepository as RepositoryContract;
use Statamic\SeoPro\Redirects\Stache\RedirectRepository as StacheRepository;

class RedirectRepository extends StacheRepository implements RepositoryContract
{
    public function query(): RedirectQueryBuilder
    {
        return app(RedirectQueryBuilder::class, [
            'builder' => RedirectModel::query(),
        ]);
    }

    public function save(Redirect $redirect): void
    {
        if (! $redirect->site()) {
            $redirect->site(Site::default()->handle());
        }

        $model = $this->toModel($redirect);
        $model->save();

        $redirect->id($model->id);
    }

    public function delete(Redirect $redirect): void
    {
        $this->toModel($redirect)->delete();
    }

    public static function fromModel(RedirectModel $model): Redirect
    {
        return app(Redirect::class)
            ->id($model->id)
            ->site($model->site)
            ->source($model->source)
            ->destination($model->destination)
            ->responseCode($model->response_code)
            ->enabled($model->enabled)
            ->hits($model->hits)
            ->lastHitAt($model->last_hit_at)
            ->data($model->data);
    }

    private function toModel(Redirect $redirect): RedirectModel
    {
        $model = RedirectModel::find($redirect->id()) ?? new RedirectModel;

        if (! is_null($redirect->id())) {
            $model->id = $redirect->id();
        }

        $model->site = $redirect->site();
        $model->source = $redirect->source();
        $model->destination = $redirect->destination();
        $model->response_code = $redirect->responseCode();
        $model->enabled = $redirect->enabled();
        $model->hits = $redirect->hits();
        $model->last_hit_at = $redirect->lastHitAt();
        $model->data = $redirect->data();

        return $model;
    }

    public static function bindings(): array
    {
        return [
            RedirectQueryBuilder::class => \Statamic\SeoPro\Redirects\Eloquent\RedirectQueryBuilder::class,
        ];
    }
}
