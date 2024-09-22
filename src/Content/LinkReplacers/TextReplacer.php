<?php

namespace Statamic\SeoPro\Content\LinkReplacers;

use Illuminate\Support\Str;
use Statamic\Fieldtypes\Text;
use Statamic\SeoPro\Content\ReplacementContext;
use Statamic\SeoPro\Contracts\TextProcessing\Links\FieldtypeLinkReplacer;

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
