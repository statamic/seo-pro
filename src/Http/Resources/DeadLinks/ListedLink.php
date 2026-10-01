<?php

namespace Statamic\SeoPro\Http\Resources\DeadLinks;

use Illuminate\Http\Resources\Json\JsonResource;
use Statamic\SeoPro\DeadLinks\ContentScanner;
use Statamic\SeoPro\DeadLinks\Link;

class ListedLink extends JsonResource
{
    protected $blueprint;

    protected $columns;

    public function blueprint($blueprint)
    {
        $this->blueprint = $blueprint;

        return $this;
    }

    public function columns($columns)
    {
        $this->columns = $columns;

        return $this;
    }

    public function toArray($request)
    {
        $link = $this->resource;

        return [
            'id' => $link->id(),
            'status' => $link->status(),

            $this->merge($this->values([
                'url' => $link->url(),
                'status_code' => $link->statusCode(),
                'consecutive_failures' => $link->consecutiveFailures(),
                'checked_at' => $link->checkedAt(),
                'error' => $link->error(),
            ])),

            'references' => $link->references()
                ->unique(fn ($reference) => Link::subjectKey($reference['subject_type'], $reference['subject_id'], $reference['site']))
                ->map(fn ($reference) => [...$reference, 'edit_url' => ContentScanner::editUrl($reference)])
                ->filter(fn ($reference) => $reference['edit_url'])
                ->values()
                ->all(),
        ];
    }

    protected function values($extra = [])
    {
        return $this->columns->mapWithKeys(function ($column) use ($extra) {
            $key = $column->field;
            $field = $this->blueprint->field($key);

            $value = $extra[$key] ?? $this->resource->get($key) ?? $field?->defaultValue();

            if (! $field) {
                return [$key => $value];
            }

            $value = $field->setValue($value)
                ->setParent($this->resource)
                ->preProcessIndex()
                ->value();

            return [$key => $value];
        });
    }
}
