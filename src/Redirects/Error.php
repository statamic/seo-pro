<?php

namespace Statamic\SeoPro\Redirects;

use Illuminate\Support\Str;
use Statamic\Data\ContainsData;
use Statamic\Data\ExistsAsFile;
use Statamic\Data\TracksQueriedColumns;
use Statamic\Data\TracksQueriedRelations;
use Statamic\Facades\Site;
use Statamic\Facades\Stache;
use Statamic\SeoPro\Events\ErrorCreated;
use Statamic\SeoPro\Events\ErrorDeleted;
use Statamic\SeoPro\Events\ErrorSaved;
use Statamic\Support\Arr;
use Statamic\Support\Traits\FluentlyGetsAndSets;
use Statamic\SeoPro\Facades\Error as ErrorFacade;

class Error
{
    use ContainsData, ExistsAsFile, FluentlyGetsAndSets, TracksQueriedColumns, TracksQueriedRelations;

    protected $id;
    protected $site;
    protected $url;
    protected $hits = 0;
    protected $lastHitAt;

    public function __construct()
    {
        $this->data = collect();
    }

    public function id($id = null)
    {
        return $this
            ->fluentlyGetOrSet('id')
            ->args(func_get_args());
    }

    public function site($site = null)
    {
        return $this
            ->fluentlyGetOrSet('site')
            ->getter(fn ($site) => $site ?? Site::default()->handle())
            ->args(func_get_args());
    }

    public function url($url = null)
    {
        return $this
            ->fluentlyGetOrSet('url')
            ->args(func_get_args());
    }

    public function hits($hits = null)
    {
        return $this
            ->fluentlyGetOrSet('hits')
            ->args(func_get_args());
    }

    public function lastHitAt($lastHitAt = null)
    {
        return $this
            ->fluentlyGetOrSet('lastHitAt')
            ->args(func_get_args());
    }

    public function save(): bool
    {
        $isNew = is_null(ErrorFacade::find($this->id()));

        ErrorFacade::save($this);

        if ($isNew) {
            ErrorCreated::dispatch($this);
        }

        ErrorSaved::dispatch($this);

        return true;
    }

    public function delete(): bool
    {
        ErrorFacade::delete($this);

        ErrorDeleted::dispatch($this);

        return true;
    }

    public function path(): string
    {
        return $this->initialPath ?? $this->buildPath();
    }

    public function buildPath(): string
    {
        $directory = rtrim(Stache::store('errors')->directory(), '/');

        if (Site::multiEnabled()) {
            return $directory.'/'.$this->site().'/'.$this->id().'.yaml';
        }

        return $directory.'/'.$this->id().'.yaml';
    }

    public function fileData(): array
    {
        return Arr::removeNullValues([
            ...$this->data->all(),
            'url' => $this->url(),
            'hits' => $this->hits() ?: null,
            'last_hit_at' => $this->lastHitAt(),
        ]);
    }

    public function getQueryableValue(string $field)
    {
        if (in_array($method = Str::camel($field), $this->queryableMethods())) {
            return $this->{$method}();
        }

        $value = $this->get($field);

        if (! $field = $this->blueprint()->field($field)) {
            return $value;
        }

        return $field->fieldtype()->toQueryableValue($value);
    }

    private function queryableMethods(): array
    {
        return [
            'id', 'site', 'url', 'hits', 'lastHitAt',
        ];
    }
}
