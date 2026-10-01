<?php

namespace Statamic\SeoPro\DeadLinks;

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
            'entry' => Facades\Entry::find($id)?->editUrl(),
            'term' => Facades\Term::find($id)?->in($site)->editUrl(),
            'global' => Facades\GlobalSet::find($id)?->in($site)?->editUrl(),
            default => (self::$resolvers[$reference['subject_type']] ?? fn () => null)($id, $site),
        };
    }
}
