<?php

namespace Statamic\SeoPro\Contracts\TextProcessing\Links;

use Statamic\SeoPro\TextProcessing\Content\ReplacementContext;

interface FieldtypeLinkReplacer
{
    public static function fieldtype(): string;

    public function canReplace(ReplacementContext $context): bool;

    public function replace(ReplacementContext $context): bool;
}