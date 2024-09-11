<?php

namespace Statamic\SeoPro\TextProcessing\Content;

use Statamic\Contracts\Entries\Entry;

readonly class ReplacementContext
{
    public function __construct(
        public Entry $entry,
        public LinkReplacement $replacement,
        public RetrievedField $field,
    )
    {
    }

    public function toViewData(): array
    {
        return [
            'entry' => $this->entry->toDeferredAugmentedArray(),
            'text' => $this->replacement->phrase,
            'url' => $this->replacement->getTarget(),
        ];
    }

    public function render(string $view): string
    {
        return (string)view('seo-pro::'.$view, $this->toViewData());
    }
}