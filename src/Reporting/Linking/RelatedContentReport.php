<?php

namespace Statamic\SeoPro\Reporting\Linking;


use Statamic\SeoPro\TextProcessing\Similarity\Result;

class RelatedContentReport extends BaseLinkReport
{
    protected array $relatedContent = [];

    public function relatedContent(?array $relatedContent = null)
    {
        return $this->fluentlyGetOrSet('relatedContent')
            ->args(func_get_args());
    }

    public function getRelated(bool $returnFullEntry = false): array
    {
        return collect($this->relatedContent)->map(function (Result $result) use ($returnFullEntry) {
            return [
                'entry' => $returnFullEntry ? $result->entry() : $this->dumpEntry($result->entry()),
                'score' => $result->score(),
                'keyword_score' => $result->keywordScore(),
                'related_keywords' => implode(', ', array_keys($result->similarKeywords()))
            ];
        })->all();
    }

    protected function extraData(): array
    {
        return [
            'related_content' => $this->getRelated(),
        ];
    }
}