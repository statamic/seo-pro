<?php

namespace Statamic\SeoPro\Redirects\Eloquent;

use Statamic\Facades\Site;
use Statamic\SeoPro\Redirects\Error;
use Statamic\SeoPro\Redirects\ErrorQueryBuilder;
use Statamic\SeoPro\Redirects\ErrorRepository as RepositoryContract;
use Statamic\SeoPro\Redirects\Stache\ErrorRepository as StacheRepository;

class ErrorRepository extends StacheRepository implements RepositoryContract
{
    public function query(): ErrorQueryBuilder
    {
        return app(ErrorQueryBuilder::class, [
            'builder' => ErrorModel::query(),
        ]);
    }

    public function save(Error $error): void
    {
        if (! $error->site()) {
            $error->site(Site::default()->handle());
        }

        $model = $this->toModel($error);
        $model->save();

        $error->id($model->id);
    }

    public function delete(Error $error): void
    {
        $this->toModel($error)->delete();
    }

    public static function fromModel(ErrorModel $model): Error
    {
        return app(Error::class)
            ->id($model->id)
            ->site($model->site)
            ->url($model->url)
            ->hits($model->hits)
            ->lastHitAt($model->last_hit_at)
            ->data($model->data);
    }

    private function toModel(Error $error): ErrorModel
    {
        $model = ErrorModel::find($error->id()) ?? new ErrorModel;

        if (! is_null($error->id())) {
            $model->id = $error->id();
        }

        $model->site = $error->site();
        $model->url = $error->url();
        $model->hits = $error->hits();
        $model->last_hit_at = $error->lastHitAt();
        $model->data = $error->data();

        return $model;
    }

    public static function bindings(): array
    {
        return [
            ErrorQueryBuilder::class => \Statamic\SeoPro\Redirects\Eloquent\ErrorQueryBuilder::class,
        ];
    }
}
