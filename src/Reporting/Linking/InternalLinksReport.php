<?php

namespace Statamic\SeoPro\Reporting\Linking;

class InternalLinksReport extends BaseLinkReport
{
    protected array $internalLinks = [];

    public function internalLinks(?array $links = null)
    {
        return $this->fluentlyGetOrSet('internalLinks')
            ->args(func_get_args());
    }

    public function getLinks(): array
    {
        return collect($this->internalLinks)->map(function ($link) {
            return [
                'entry' => $this->dumpEntry($link['entry']),
                'uri' => $link['uri'],
            ];
        })->all();
    }

    protected function extraData(): array
    {
        return [
            'links' => $this->getLinks(),
        ];
    }
}