<?php

namespace Statamic\SeoPro\BrokenLinks;

use Illuminate\Support\Carbon;
use Statamic\Contracts\Query\ContainsQueryableValues;
use Statamic\Data\ExistsAsFile;
use Statamic\Data\TracksQueriedColumns;
use Statamic\Data\TracksQueriedRelations;
use Statamic\Facades\Site;
use Statamic\Facades\Stache;
use Statamic\SeoPro\Facades\ExternalLink as ExternalLinkFacade;
use Statamic\Support\Arr;
use Statamic\Support\Str;
use Statamic\Support\Traits\FluentlyGetsAndSets;
use Symfony\Component\HttpFoundation\Response;

class ExternalLink implements ContainsQueryableValues
{
    use ExistsAsFile, FluentlyGetsAndSets, TracksQueriedColumns, TracksQueriedRelations;

    protected $id;
    protected $site;
    protected $url;
    protected $statusCode;
    protected $error;
    protected $brokenSince;
    protected $checkedAt;
    protected $nextCheckAt;
    protected $notifiedAt;
    protected $references = [];

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

    public function statusCode($statusCode = null)
    {
        return $this
            ->fluentlyGetOrSet('statusCode')
            ->args(func_get_args());
    }

    public function error($error = null)
    {
        return $this
            ->fluentlyGetOrSet('error')
            ->args(func_get_args());
    }

    public function brokenSince($brokenSince = null)
    {
        return $this
            ->fluentlyGetOrSet('brokenSince')
            ->setter(fn ($value) => $value ? Carbon::parse($value) : null)
            ->args(func_get_args());
    }

    public function checkedAt($checkedAt = null)
    {
        return $this
            ->fluentlyGetOrSet('checkedAt')
            ->setter(fn ($value) => $value ? Carbon::parse($value) : null)
            ->args(func_get_args());
    }

    public function nextCheckAt($nextCheckAt = null)
    {
        return $this
            ->fluentlyGetOrSet('nextCheckAt')
            ->setter(fn ($value) => $value ? Carbon::parse($value) : null)
            ->args(func_get_args());
    }

    public function notifiedAt($notifiedAt = null)
    {
        return $this
            ->fluentlyGetOrSet('notifiedAt')
            ->setter(fn ($value) => $value ? Carbon::parse($value) : null)
            ->args(func_get_args());
    }

    public function response(): ?string
    {
        if ($this->statusCode()) {
            return trim($this->statusCode().' '.(Response::$statusTexts[$this->statusCode()] ?? ''));
        }

        if ($this->error()) {
            return __("seo-pro::messages.broken_link_errors.{$this->error()}");
        }

        return null;
    }

    public function references($references = null)
    {
        return $this
            ->fluentlyGetOrSet('references')
            ->getter(fn ($references) => collect($references))
            ->setter(function ($references) {
                return collect($references)
                    ->map(fn ($reference) => $reference instanceof Reference ? $reference : Reference::fromKey($reference))
                    ->values()
                    ->all();
            })
            ->args(func_get_args());
    }

    public function getQueryableValue(string $field)
    {
        if ($field === 'references') {
            return $this->references()->map->key()->all();
        }

        return $this->{Str::camel($field)}();
    }

    public function save(): bool
    {
        ExternalLinkFacade::save($this);

        return true;
    }

    public function delete(): bool
    {
        ExternalLinkFacade::delete($this);

        return true;
    }

    public function path(): string
    {
        return $this->initialPath ?? $this->buildPath();
    }

    public function buildPath(): string
    {
        $directory = rtrim(Stache::store('seo_pro_external_links')->directory(), '/');

        if (Site::multiEnabled()) {
            return $directory.'/'.$this->site().'/'.$this->id().'.yaml';
        }

        return $directory.'/'.$this->id().'.yaml';
    }

    public function fileData(): array
    {
        return Arr::removeNullValues([
            'url' => $this->url(),
            'status_code' => $this->statusCode(),
            'error' => $this->error(),
            'broken_since' => $this->brokenSince(),
            'checked_at' => $this->checkedAt(),
            'next_check_at' => $this->nextCheckAt(),
            'notified_at' => $this->notifiedAt(),
            'references' => $this->references()->map->key()->all() ?: null,
        ]);
    }
}
