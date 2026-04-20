<?php

namespace Statamic\SeoPro\Redirects;

use Illuminate\Support\Str;
use Statamic\Data\ContainsData;
use Statamic\Data\ExistsAsFile;
use Statamic\Data\TracksQueriedColumns;
use Statamic\Data\TracksQueriedRelations;
use Statamic\Facades\Stache;
use Statamic\Fields\Blueprint;
use Statamic\SeoPro\Events\RedirectCreated;
use Statamic\SeoPro\Events\RedirectDeleted;
use Statamic\SeoPro\Events\RedirectSaved;
use Statamic\SeoPro\Facades\Redirect as RedirectFacade;
use Statamic\Support\Arr;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class Redirect
{
    use ContainsData, ExistsAsFile, FluentlyGetsAndSets, TracksQueriedColumns, TracksQueriedRelations;

    protected $id;
    protected $sourceUrl;
    protected $destinationUrl;
    protected $statusCode;
    protected $enabled;

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

    public function sourceUrl($sourceUrl = null)
    {
        return $this
            ->fluentlyGetOrSet('sourceUrl')
            ->args(func_get_args());
    }

    public function destinationUrl($destinationUrl = null)
    {
        return $this
            ->fluentlyGetOrSet('destinationUrl')
            ->args(func_get_args());
    }

    public function statusCode($statusCode = null)
    {
        return $this
            ->fluentlyGetOrSet('statusCode')
            ->args(func_get_args());
    }

    public function enabled($enabled = null)
    {
        return $this
            ->fluentlyGetOrSet('enabled')
            ->args(func_get_args());
    }

    public function status(): string
    {
        return $this->enabled() ? 'active' : 'inactive';
    }

    public function blueprint(): Blueprint
    {
        return RedirectFacade::blueprint();
    }

    public function save(): bool
    {
        $isNew = is_null(RedirectFacade::find($this->id()));

        RedirectFacade::save($this);

        if ($isNew) {
            RedirectCreated::dispatch($this);
        }

        RedirectSaved::dispatch($this);

        return true;
    }

    public function delete(): bool
    {
        RedirectFacade::delete($this);

        RedirectDeleted::dispatch($this);

        return true;
    }

    public function path(): string
    {
        return $this->initialPath ?? $this->buildPath();
    }

    public function buildPath(): string
    {
        return vsprintf('%s/%s.yaml', [
            rtrim(Stache::store('redirects')->directory(), '/'),
            $this->id(),
        ]);
    }

    public function fileData(): array
    {
        return Arr::removeNullValues([
            ...$this->data->all(),
            'source_url' => $this->sourceUrl(),
            'destination_url' => $this->destinationUrl(),
            'status_code' => $this->statusCode(),
            'enabled' => $this->enabled(),
        ]);
    }

    public function editUrl(): string
    {
        return cp_route('seo-pro.redirects.edit', $this->id());
    }

    public function updateUrl(): string
    {
        return cp_route('seo-pro.redirects.update', $this->id());
    }

    public function deleteUrl(): string
    {
        return cp_route('seo-pro.redirects.destroy', $this->id());
    }

    public function getQueryableValue(string $field)
    {
        if (in_array($method = Str::camel($field), $this->queryableMethods())) {
            return $this->{$method}();
        }
    }

    private function queryableMethods(): array
    {
        return [
            'id', 'sourceUrl', 'destinationUrl', 'statusCode', 'enabled',
        ];
    }
}
