<?php

namespace Statamic\SeoPro\Contracts\Linking\Links;

use Statamic\SeoPro\Content\ReplacementContext;

interface FieldtypeLinkReplacer
{
    public static function fieldtype(): string;

    public function canReplace(ReplacementContext $context): bool;

    public function replace(ReplacementContext $context): bool;
}
