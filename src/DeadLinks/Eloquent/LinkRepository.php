<?php

namespace Statamic\SeoPro\DeadLinks\Eloquent;

use Statamic\Facades\Site;
use Statamic\SeoPro\DeadLinks\Link;
use Statamic\SeoPro\DeadLinks\LinkQueryBuilder;
use Statamic\SeoPro\DeadLinks\LinkRepository as RepositoryContract;
use Statamic\SeoPro\DeadLinks\Stache\LinkRepository as StacheRepository;

class LinkRepository extends StacheRepository implements RepositoryContract
{
    public function query(): LinkQueryBuilder
    {
        return app(LinkQueryBuilder::class, [
            'builder' => LinkModel::query(),
        ]);
    }

    public function save(Link $link): void
    {
        if (! $link->site()) {
            $link->site(Site::default()->handle());
        }

        $model = $this->toModel($link);
        $model->save();

        $link->id($model->id);
    }

    public function delete(Link $link): void
    {
        $this->toModel($link)->delete();
    }

    public static function fromModel(LinkModel $model): Link
    {
        return app(Link::class)
            ->id($model->id)
            ->site($model->site)
            ->url($model->url)
            ->status($model->status)
            ->statusCode($model->status_code)
            ->error($model->error)
            ->consecutiveFailures($model->consecutive_failures)
            ->checkedAt($model->checked_at)
            ->nextCheckAt($model->next_check_at)
            ->notifiedAt($model->notified_at)
            ->references($model->references ?? [])
            ->data($model->data ?? []);
    }

    private function toModel(Link $link): LinkModel
    {
        $model = LinkModel::find($link->id()) ?? new LinkModel;

        if (! is_null($link->id())) {
            $model->id = $link->id();
        }

        $model->site = $link->site();
        $model->url = $link->url();
        $model->status = $link->status();
        $model->status_code = $link->statusCode();
        $model->error = $link->error();
        $model->consecutive_failures = $link->consecutiveFailures();
        $model->checked_at = $link->checkedAt();
        $model->next_check_at = $link->nextCheckAt();
        $model->notified_at = $link->notifiedAt();
        $model->references = $link->references()->all();
        $model->data = $link->data();

        return $model;
    }

    public static function bindings(): array
    {
        return [
            LinkQueryBuilder::class => \Statamic\SeoPro\DeadLinks\Eloquent\LinkQueryBuilder::class,
        ];
    }
}
