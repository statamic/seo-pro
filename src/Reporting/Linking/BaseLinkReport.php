<?php

namespace Statamic\SeoPro\Reporting\Linking;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Statamic\Contracts\Entries\Entry;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class BaseLinkReport implements Arrayable, Jsonable
{
    use FluentlyGetsAndSets;

    protected int $internalLinkCount = 0;
    protected int $externalLinkCount = 0;
    protected int $inboundInternalLinkCount = 0;
    protected int $minInternalLinkCount = 3;
    protected int $maxInternalLinkCount = 6;
    protected int $minExternalLinkCount = 0;
    protected int $maxExternalLinkCount = 0;
    protected ?Entry $entry = null;

    public function internalLinkCount(?int $internalLinkCount = null)
    {
        return $this->fluentlyGetOrSet('internalLinkCount')
            ->args(func_get_args());
    }

    public function externalLinkCount(?int $externalLinkCount = null)
    {
        return $this->fluentlyGetOrSet('externalLinkCount')
            ->args(func_get_args());
    }

    public function inboundInternalLinkCount(?int $linkCount = null)
    {
        return $this->fluentlyGetOrSet('inboundInternalLinkCount')
            ->args(func_get_args());
    }

    public function minInternalLinkCount(?int $count = null)
    {
        return $this->fluentlyGetOrSet('minInternalLinkCount')
            ->args(func_get_args());
    }

    public function maxInternalLinkCount(?int $count = null)
    {
        return $this->fluentlyGetOrSet('maxInternalLinkCount')
            ->args(func_get_args());
    }

    public function minExternalLinkCount(?int $count = null)
    {
        return $this->fluentlyGetOrSet('minExternalLinkCount')
            ->args(func_get_args());
    }

    public function maxExternalLinkCount(?int $count = null)
    {
        return $this->fluentlyGetOrSet('maxExternalLinkCount')
            ->args(func_get_args());
    }

    public function entry(?Entry $entry = null)
    {
        return $this->fluentlyGetOrSet('entry')
            ->args(func_get_args());
    }

    protected function extraData(): array
    {
        return [];
    }

    protected function dumpEntry(?Entry $entry): ?array
    {
        if (! $entry) {
            return [];
        }

        return [
            'title' => $entry->title,
            'url' => $entry->absoluteUrl(),
            'edit_url' => $entry->editUrl(),
            'uri' => $entry->uri,
            'id' => $entry->id(),
            'site'=> $entry->site()?->handle() ?? 'default',
        ];
    }

    public function toArray(): array
    {
        return array_merge([
            'entry' => $this->dumpEntry($this->entry),
            'overview' => [
                'internal_link_count' => $this->internalLinkCount,
                'external_link_count' => $this->externalLinkCount,
                'inbound_internal_link_count' => $this->inboundInternalLinkCount,
            ],
            'preferences' => [
                'min_internal_link_count' => $this->minInternalLinkCount,
                'max_internal_link_count' => $this->maxInternalLinkCount,
                'min_external_link_count' => $this->minExternalLinkCount,
                'max_external_link_count' => $this->maxExternalLinkCount,
            ],
        ], $this->extraData());
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toArray());
    }
}