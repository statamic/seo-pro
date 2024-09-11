<?php

namespace Statamic\SeoPro\TextProcessing\Content\LinkReplacers;

use Statamic\Fieldtypes\Bard;
use Statamic\SeoPro\Contracts\TextProcessing\Links\FieldtypeLinkReplacer;
use Statamic\SeoPro\TextProcessing\Content\ReplacementContext;

class BardReplacer implements FieldtypeLinkReplacer
{
    public static function fieldtype(): string
    {
        return Bard::handle();
    }

    public function canReplace(ReplacementContext $context): bool
    {
        // This is all in progress.
        return false;
    }

    public function replace(ReplacementContext $context): bool
    {
        // This is all in progress.
        return false;
    }
}