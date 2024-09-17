<?php

namespace Statamic\SeoPro\TextProcessing\Suggestions;

use Statamic\Support\Traits\FluentlyGetsAndSets;

class LinkResults
{
    use FluentlyGetsAndSets;

    protected array $internalLinks = [];

    protected array $externalLinks = [];

    /**
     * @return ($links is null ? (array{array{href:string,text:string,content:string}}) : null)
     */
    public function internalLinks(?array $links = null)
    {
        return $this->fluentlyGetOrSet('internalLinks')
            ->args(func_get_args());
    }

    /**
     * @return ($links is null ? array{array{href:string,text:string,content:string}} : null)
     */
    public function externalLinks(?array $links = null)
    {
        return $this->fluentlyGetOrSet('externalLinks')
            ->args(func_get_args());
    }

    /**
     * @return array{array{href:string,text:string,content:string}}
     */
    public function allLinks(): array
    {
        return array_merge(
            $this->internalLinks(),
            $this->externalLinks(),
        );
    }
}
