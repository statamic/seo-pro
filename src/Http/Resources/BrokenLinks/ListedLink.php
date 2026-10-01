<?php

namespace Statamic\SeoPro\Http\Resources\BrokenLinks;

use Illuminate\Http\Resources\Json\JsonResource;
use Statamic\SeoPro\BrokenLinks\EditUrls;
use Statamic\SeoPro\BrokenLinks\ExternalLink;

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

            $this->merge($this->values([
                'url' => $link->url(),
                'failing_since' => $link->failingSince(),
                'response' => $link->response(),
                'checked_at' => $link->checkedAt(),
            ])),

            'references' => $link->references()
                ->unique(fn ($reference) => ExternalLink::subjectKey($reference['subject_type'], $reference['subject_id'], $reference['site']))
                ->map(fn ($reference) => [...$reference, 'edit_url' => EditUrls::for($reference)])
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
