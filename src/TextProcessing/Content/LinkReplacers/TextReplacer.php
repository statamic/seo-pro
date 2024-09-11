<?php

namespace Statamic\SeoPro\TextProcessing\Content\LinkReplacers;

use Illuminate\Support\Str;
use Statamic\Fieldtypes\Text;
use Statamic\SeoPro\Contracts\TextProcessing\Links\FieldtypeLinkReplacer;
use Statamic\SeoPro\TextProcessing\Content\ReplacementContext;

class TextReplacer implements FieldtypeLinkReplacer
{
    public static function fieldtype(): string
    {
        return Text::handle();
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
        $html = Str::replaceFirst(
            $context->replacement->phrase,
            $context->render('html'),
            $context->field->getValue()
        );

        $context->field->update($html)->save();

        return true;
    }
}