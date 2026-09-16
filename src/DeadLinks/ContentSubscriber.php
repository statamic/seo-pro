<?php

namespace Statamic\SeoPro\DeadLinks;

use Statamic\Events\EntryDeleted;
use Statamic\Events\EntrySaved;
use Statamic\Events\GlobalVariablesDeleted;
use Statamic\Events\GlobalVariablesSaved;
use Statamic\Events\TermDeleted;
use Statamic\Events\TermSaved;

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
        $entry = $event->entry;

        ContentScanner::syncForSubject(
            'entry',
            (string) $entry->id(),
            (string) $entry->locale(),
            $entry->data()->all(),
            $entry->blueprint(),
            (string) ($entry->get('title') ?? $entry->id()),
            method_exists($entry, 'editUrl') ? $entry->editUrl() : null
        );
    }

    public function handleEntryDeleted(EntryDeleted $event): void
    {
        $entry = $event->entry;

        ContentScanner::deleteForSubject('entry', (string) $entry->id(), (string) $entry->locale());
    }

    public function handleTermSaved(TermSaved $event): void
    {
        $term = $event->term;

        ContentScanner::syncForSubject(
            'term',
            (string) $term->id(),
            (string) $term->locale(),
            $term->data()->all(),
            $term->blueprint(),
            (string) ($term->get('title') ?? $term->slug()),
            method_exists($term, 'editUrl') ? $term->editUrl() : null
        );
    }

    public function handleTermDeleted(TermDeleted $event): void
    {
        $term = $event->term;

        ContentScanner::deleteForSubject('term', (string) $term->id(), (string) $term->locale());
    }

    public function handleGlobalVariablesSaved(GlobalVariablesSaved $event): void
    {
        $variables = $event->variables;

        ContentScanner::syncForSubject(
            'global',
            (string) $variables->handle(),
            (string) $variables->locale(),
            $variables->data()->all(),
            $variables->blueprint(),
            (string) $variables->title(),
            method_exists($variables, 'editUrl') ? $variables->editUrl() : null
        );
    }

    public function handleGlobalVariablesDeleted(GlobalVariablesDeleted $event): void
    {
        $variables = $event->variables;

        ContentScanner::deleteForSubject('global', (string) $variables->handle(), (string) $variables->locale());
    }
}
