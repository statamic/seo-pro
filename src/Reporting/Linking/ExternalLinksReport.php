<?php

namespace Statamic\SeoPro\Reporting\Linking;

class ExternalLinksReport extends BaseLinkReport
{
    protected array $externalLinks = [];

    public function externalLinks(?array $links = null)
    {
        return $this->fluentlyGetOrSet('externalLinks')
            ->args(func_get_args());
    }

    public function getLinks(): array
    {
        return collect($this->externalLinks)->map(function ($link) {
            return [
                'link' => $link,
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