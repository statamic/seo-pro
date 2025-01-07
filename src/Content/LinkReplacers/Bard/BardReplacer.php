<?php

namespace Statamic\SeoPro\Content\LinkReplacers\Bard;

use Statamic\Fieldtypes\Bard;
use Statamic\SeoPro\Content\ReplacementContext;
use Statamic\SeoPro\Contracts\Linking\Links\FieldtypeLinkReplacer;

class BardReplacer implements FieldtypeLinkReplacer
{
    public function __construct(
        protected readonly BardManipulator $bardManipulator,
    ) {}

    public static function fieldtype(): string
    {
        return Bard::handle();
    }

    public function canReplace(ReplacementContext $context): bool
    {
        return $this->bardManipulator->canInsertLink(
            $context->field->getValue(),
            $context->replacement->phrase,
        );
    }

    public function replace(ReplacementContext $context): bool
    {
        $currentContent = $context->field->getValue();
        $updatedContent = $this->bardManipulator->replaceFirstWithLink(
            $currentContent,
            $context->replacement->phrase,
            new BardLink(
                $context->replacement->getTarget()
            ),
        );

        $context->field->update($updatedContent)->save();

        return json_encode($currentContent) == json_encode($updatedContent);
    }
}
