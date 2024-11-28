<?php

namespace Statamic\SeoPro\Reporting\Linking;

class InternalLinksReport extends BaseLinkReport
{
    protected array $internalLinks = [];
    protected $user;

    public function forUser($user): static
    {
        $this->user = $user;

        return $this;
    }

    public function internalLinks(?array $links = null)
    {
        return $this->fluentlyGetOrSet('internalLinks')
            ->args(func_get_args());
    }

    public function getLinks(): array
    {
        return collect($this->internalLinks)->map(function ($link) {
            $canEdit = false;

            if ($this->user && $link['entry'] !== null) {
                $canEdit = $this->user->can('edit', $link['entry']);
            }

            return [
                'entry' => $this->dumpEntry($link['entry']),
                'can_edit_entry' => $canEdit,
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
