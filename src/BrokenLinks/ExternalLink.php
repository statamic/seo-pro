<?php

namespace Statamic\SeoPro\BrokenLinks;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Statamic\Data\ContainsData;
use Statamic\Data\ExistsAsFile;
use Statamic\Data\TracksQueriedColumns;
use Statamic\Data\TracksQueriedRelations;
use Statamic\Facades\Site;
use Statamic\Facades\Stache;
use Statamic\SeoPro\Facades\ExternalLink as ExternalLinkFacade;
use Statamic\Support\Arr;
use Statamic\Support\Traits\FluentlyGetsAndSets;
use Symfony\Component\HttpFoundation\Response;

class ExternalLink
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
    protected $failingSince;
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

    public function failingSince($failingSince = null)
    {
        return $this
            ->fluentlyGetOrSet('failingSince')
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

    /**
     * Every place this link has been found: [['subject_type', 'subject_id', 'site', 'field_path', 'title']].
     */
    public function references($references = null)
    {
        return $this
            ->fluentlyGetOrSet('references')
            ->getter(fn ($value) => collect($value ?? []))
            ->setter(fn ($value) => collect($value)->values()->all())
            ->args(func_get_args());
    }

    public function subjects(): array
    {
        return $this->references()
            ->map(fn ($reference) => self::subjectKey($reference['subject_type'], $reference['subject_id'], $reference['site']))
            ->unique()
            ->values()
            ->all();
    }

    public static function subjectKey(string $subjectType, string $subjectId, string $site): string
    {
        return "{$subjectType}::{$subjectId}::{$site}";
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
            ...$this->data->all(),
            'url' => $this->url(),
            'status' => $this->status(),
            'status_code' => $this->statusCode(),
            'error' => $this->error(),
            'failing_since' => $this->failingSince(),
            'checked_at' => $this->checkedAt(),
            'next_check_at' => $this->nextCheckAt(),
            'notified_at' => $this->notifiedAt(),
            'references' => $this->references()->isEmpty() ? null : $this->references()->all(),
        ]);
    }

    public function getQueryableValue(string $field)
    {
        if (! in_array($method = Str::camel($field), $this->queryableMethods())) {
            return null;
        }

        return $this->{$method}();
    }

    private function queryableMethods(): array
    {
        return [
            'id', 'site', 'url', 'status', 'failingSince',
            'checkedAt', 'nextCheckAt', 'notifiedAt', 'subjects',
        ];
    }
}
