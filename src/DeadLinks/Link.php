<?php

namespace Statamic\SeoPro\DeadLinks;

use Illuminate\Support\Str;
use Statamic\Data\ContainsData;
use Statamic\Data\ExistsAsFile;
use Statamic\Data\TracksQueriedColumns;
use Statamic\Data\TracksQueriedRelations;
use Statamic\Facades\Site;
use Statamic\Facades\Stache;
use Statamic\Fields\Blueprint;
use Statamic\SeoPro\Events\DeadLinkCreated;
use Statamic\SeoPro\Events\DeadLinkDeleted;
use Statamic\SeoPro\Events\DeadLinkSaved;
use Statamic\SeoPro\Facades\DeadLink as DeadLinkFacade;
use Statamic\Support\Arr;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class Link
{
    use ContainsData, ExistsAsFile, FluentlyGetsAndSets, TracksQueriedColumns, TracksQueriedRelations;

    const STATUS_PENDING = 'pending';
    const STATUS_OK = 'ok';
    const STATUS_FAILING = 'failing';

    protected $id;
    protected $site;
    protected $url;
    protected $status;
    protected $statusCode;
    protected $error;
    protected $consecutiveFailures = 0;
    protected $checkedAt;
    protected $nextCheckAt;
    protected $notifiedAt;
    protected $references = [];

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

    public function host(): ?string
    {
        return parse_url($this->url() ?? '', PHP_URL_HOST) ?: null;
    }

    public function status($status = null)
    {
        return $this
            ->fluentlyGetOrSet('status')
            ->getter(fn ($status) => $status ?? self::STATUS_PENDING)
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

    public function consecutiveFailures($consecutiveFailures = null)
    {
        return $this
            ->fluentlyGetOrSet('consecutiveFailures')
            ->getter(fn ($value) => $value ?? 0)
            ->args(func_get_args());
    }

    public function checkedAt($checkedAt = null)
    {
        return $this
            ->fluentlyGetOrSet('checkedAt')
            ->args(func_get_args());
    }

    public function nextCheckAt($nextCheckAt = null)
    {
        return $this
            ->fluentlyGetOrSet('nextCheckAt')
            ->args(func_get_args());
    }

    public function notifiedAt($notifiedAt = null)
    {
        return $this
            ->fluentlyGetOrSet('notifiedAt')
            ->args(func_get_args());
    }

    /**
     * Every place this link has been found: [['subject_type', 'subject_id', 'site', 'field_path', 'title', 'edit_url']].
     */
    public function references($references = null)
    {
        return $this
            ->fluentlyGetOrSet('references')
            ->getter(fn ($value) => collect($value ?? []))
            ->setter(fn ($value) => collect($value)->values()->all())
            ->args(func_get_args());
    }

    public function isFailing(): bool
    {
        return $this->status() === self::STATUS_FAILING;
    }

    public function blueprint(): Blueprint
    {
        return DeadLinkFacade::blueprint();
    }

    public function save(): bool
    {
        $isNew = is_null(DeadLinkFacade::find($this->id()));

        DeadLinkFacade::save($this);

        if ($isNew) {
            DeadLinkCreated::dispatch($this);
        }

        DeadLinkSaved::dispatch($this);

        return true;
    }

    public function delete(): bool
    {
        DeadLinkFacade::delete($this);

        DeadLinkDeleted::dispatch($this);

        return true;
    }

    public function path(): string
    {
        return $this->initialPath ?? $this->buildPath();
    }

    public function buildPath(): string
    {
        $directory = rtrim(Stache::store('seo_pro_dead_links')->directory(), '/');

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
            'status' => $this->status(),
            'status_code' => $this->statusCode(),
            'error' => $this->error(),
            'consecutive_failures' => $this->consecutiveFailures() ?: null,
            'checked_at' => $this->checkedAt(),
            'next_check_at' => $this->nextCheckAt(),
            'notified_at' => $this->notifiedAt(),
            'references' => $this->references()->isEmpty() ? null : $this->references()->all(),
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
            'id', 'site', 'url', 'host', 'status', 'statusCode', 'error',
            'consecutiveFailures', 'checkedAt', 'nextCheckAt', 'notifiedAt',
        ];
    }
}
