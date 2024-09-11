<?php

namespace Statamic\SeoPro\TextProcessing\Suggestions;

use Statamic\Support\Traits\FluentlyGetsAndSets;

class LinkResults
{
    use FluentlyGetsAndSets;

    protected array $internalLinks = [];

    protected array $externalLinks = [];

    /**
     * @param array|null $links
     * @return ($links is null ? (array{array{href:string,text:string}}) : null)
     */
    public function internalLinks(?array $links = null)
    {
        return $this->fluentlyGetOrSet('internalLinks')
            ->args(func_get_args());
    }

    /**
     * @param array|null $links
     * @return ($links is null ? array{array{href:string,text:string}} : null)
     */
    public function externalLinks(?array $links = null)
    {
        return $this->fluentlyGetOrSet('externalLinks')
            ->args(func_get_args());
    }
}