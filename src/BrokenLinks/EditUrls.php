<?php

namespace Statamic\SeoPro\BrokenLinks;

use Closure;
use Statamic\Facades;

class EditUrls
{
    private static array $resolvers = [];

    public static function resolveUsing(string $subjectType, Closure $resolver): void
    {
        self::$resolvers[$subjectType] = $resolver;
    }

    public static function for(array $reference): ?string
    {
        $id = $reference['subject_id'];
        $site = $reference['site'];

        return match ($reference['subject_type']) {
            'entry' => self::editUrl(Facades\Entry::find($id)),
            'term' => self::editUrl(Facades\Term::find($id)?->in($site)),
            'global' => self::editUrl(Facades\GlobalSet::find($id)?->in($site)),
            default => (self::$resolvers[$reference['subject_type']] ?? fn () => null)($id, $site),
        };
    }

    private static function editUrl($item): ?string
    {
        if (! $item || ! Facades\User::current()->can('edit', $item)) {
            return null;
        }

        return $item->editUrl();
    }
}
