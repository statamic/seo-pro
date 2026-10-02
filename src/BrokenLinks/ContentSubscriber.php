<?php

namespace Statamic\SeoPro\BrokenLinks;

use Statamic\Events\EntryDeleted;
use Statamic\Events\EntrySaved;
use Statamic\Events\GlobalVariablesDeleted;
use Statamic\Events\GlobalVariablesSaved;
use Statamic\Events\TermDeleted;
use Statamic\Events\TermSaved;
use Statamic\Taxonomies\LocalizedTerm;

class ContentSubscriber
{
    protected array $events = [
        EntrySaved::class => 'handleEntrySaved',
        EntryDeleted::class => 'handleEntryDeleted',
        TermSaved::class => 'handleTermSaved',
        TermDeleted::class => 'handleTermDeleted',
        GlobalVariablesSaved::class => 'handleGlobalVariablesSaved',
        GlobalVariablesDeleted::class => 'handleGlobalVariablesDeleted',
    ];

    public function subscribe($events): void
    {
        foreach ($this->events as $event => $method) {
            $events->listen($event, self::class.'@'.$method);
        }
    }

    public function handleEntrySaved(EntrySaved $event): void
    {
        ContentScanner::scan(
            type: 'entry',
            id: $event->entry->id(),
            values: $event->entry->data()->all(),
            site: $event->entry->locale(),
            blueprint: $event->entry->blueprint(),
        );
    }

    public function handleEntryDeleted(EntryDeleted $event): void
    {
        ContentScanner::forget('entry', $event->entry->id(), $event->entry->locale());
    }

    public function handleTermSaved(TermSaved $event): void
    {
        $event->term->localizations()->each(fn (LocalizedTerm $term) => ContentScanner::scan(
            type: 'term',
            id: $term->id(),
            values: $term->data()->all(),
            site: $term->locale(),
            blueprint: $term->blueprint(),
        ));
    }

    public function handleTermDeleted(TermDeleted $event): void
    {
        $event->term->localizations()->each(function (LocalizedTerm $term): void {
            ContentScanner::forget('term', $term->id(), $term->locale());
        });
    }

    public function handleGlobalVariablesSaved(GlobalVariablesSaved $event): void
    {
        ContentScanner::scan(
            type: 'global',
            id: $event->variables->handle(),
            values: $event->variables->data()->all(),
            site: $event->variables->locale(),
            blueprint: $event->variables->blueprint(),
        );
    }

    public function handleGlobalVariablesDeleted(GlobalVariablesDeleted $event): void
    {
        ContentScanner::forget('global', $event->variables->handle(), $event->variables->locale());
    }
}
