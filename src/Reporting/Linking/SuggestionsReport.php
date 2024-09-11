<?php

namespace Statamic\SeoPro\Reporting\Linking;

class SuggestionsReport extends BaseLinkReport
{
    protected array $suggestions = [];

    public function suggestions(?array $suggestions = null)
    {
        return $this->fluentlyGetOrSet('suggestions')
            ->args(func_get_args());
    }

    protected function extraData(): array
    {
        return [
            'suggestions' => $this->suggestions,
        ];
    }
}