<?php

namespace Statamic\SeoPro\Content\LinkReplacers;

use Illuminate\Support\Str;
use Statamic\Fieldtypes\Markdown;
use Statamic\SeoPro\Content\ReplacementContext;
use Statamic\SeoPro\Contracts\TextProcessing\Links\FieldtypeLinkReplacer;

class MarkdownReplacer implements FieldtypeLinkReplacer
{
    public static function fieldtype(): string
    {
        return Markdown::handle();
    }

    public function canReplace(ReplacementContext $context): bool
    {
        return Str::contains(
            $context->field->getValue(),
            $context->replacement->phrase
        );
    }

    public function replace(ReplacementContext $context): bool
    {
        $markdown = Str::replaceFirst(
            $context->replacement->phrase,
            $context->render('markdown'),
            $context->field->getValue()
        );

        $context->field->update($markdown)->save();

        return true;
    }
}
