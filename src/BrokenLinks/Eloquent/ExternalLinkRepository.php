<?php

namespace Statamic\SeoPro\BrokenLinks\Eloquent;

use Statamic\SeoPro\BrokenLinks\ExternalLink;
use Statamic\SeoPro\BrokenLinks\ExternalLinkQueryBuilder;
use Statamic\SeoPro\BrokenLinks\ExternalLinkRepository as RepositoryContract;
use Statamic\SeoPro\BrokenLinks\Stache\ExternalLinkRepository as StacheRepository;

class ExternalLinkRepository extends StacheRepository implements RepositoryContract
{
    public function query(): ExternalLinkQueryBuilder
    {
        return app(ExternalLinkQueryBuilder::class, [
            'builder' => ExternalLinkModel::query(),
        ]);
    }

    public function findByUrl(string $url, string $site): ?ExternalLink
    {
        $model = ExternalLinkModel::query()
            ->where('site', $site)
            ->where('url_hash', hash('sha256', $url))
            ->first();

        return $model ? static::fromModel($model) : null;
    }

    public function save(ExternalLink $link): void
    {
        $model = $this->toModel($link);
        $model->save();

        $link->id($model->id);
    }

    public function delete(ExternalLink $link): void
    {
        $this->toModel($link)->delete();
    }

    public static function fromModel(ExternalLinkModel $model): ExternalLink
    {
        return app(ExternalLink::class)
            ->id($model->id)
            ->site($model->site)
            ->url($model->url)
            ->status($model->status)
            ->statusCode($model->status_code)
            ->error($model->error)
            ->brokenSince($model->broken_since)
            ->checkedAt($model->checked_at)
            ->nextCheckAt($model->next_check_at)
            ->notifiedAt($model->notified_at)
            ->references($model->references);
    }

    private function toModel(ExternalLink $link): ExternalLinkModel
    {
        $model = ExternalLinkModel::find($link->id()) ?? new ExternalLinkModel;

        if (! is_null($link->id())) {
            $model->id = $link->id();
        }

        $model->site = $link->site();
        $model->url = $link->url();
        $model->status = $link->status();
        $model->status_code = $link->statusCode();
        $model->error = $link->error();
        $model->broken_since = $link->brokenSince();
        $model->checked_at = $link->checkedAt();
        $model->next_check_at = $link->nextCheckAt();
        $model->notified_at = $link->notifiedAt();
        $model->references = $link->references()->map->toArray()->all();
        $model->subjects = $link->subjects();

        return $model;
    }

    public static function bindings(): array
    {
        return [
            ExternalLinkQueryBuilder::class => \Statamic\SeoPro\BrokenLinks\Eloquent\ExternalLinkQueryBuilder::class,
        ];
    }
}
