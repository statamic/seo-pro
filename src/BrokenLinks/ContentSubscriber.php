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
        ContentScanner::scanEntry($event->entry);
    }

    public function handleEntryDeleted(EntryDeleted $event): void
    {
        ContentScanner::forget('entry', $event->entry->id(), $event->entry->locale());
    }

    public function handleTermSaved(TermSaved $event): void
    {
        $event->term->localizations()->each(fn (LocalizedTerm $term) => ContentScanner::scanTerm($term));
    }

    public function handleTermDeleted(TermDeleted $event): void
    {
        $event->term->localizations()->each(fn (LocalizedTerm $term) => ContentScanner::forget('term', $term->id(), $term->locale()));
    }

    public function handleGlobalVariablesSaved(GlobalVariablesSaved $event): void
    {
        ContentScanner::scanGlobal($event->variables);
    }

    public function handleGlobalVariablesDeleted(GlobalVariablesDeleted $event): void
    {
        ContentScanner::forget('global', $event->variables->handle(), $event->variables->locale());
    }
}
