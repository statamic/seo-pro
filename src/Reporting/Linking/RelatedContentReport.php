<?php

namespace Statamic\SeoPro\Reporting\Linking;

use Statamic\SeoPro\Linking\Similarity\Result;

class RelatedContentReport extends BaseLinkReport
{
    protected array $relatedContent = [];
    protected $user;

    public function forUser($user): static
    {
        $this->user = $user;

        return $this;
    }

    public function relatedContent(?array $relatedContent = null)
    {
        return $this->fluentlyGetOrSet('relatedContent')
            ->args(func_get_args());
    }

    public function getRelated(bool $returnFullEntry = false): array
    {
        return collect($this->relatedContent)->map(function (Result $result) use ($returnFullEntry) {
            $canEdit = false;

            if ($this->user) {
                $canEdit = $this->user->can('edit', $result->entry());
            }

            return [
                'entry' => $returnFullEntry ? $result->entry() : $this->dumpEntry($result->entry()),
                'can_edit_entry' => $canEdit,
                'score' => $result->score(),
                'keyword_score' => $result->keywordScore(),
                'related_keywords' => implode(', ', array_keys($result->similarKeywords())),
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
