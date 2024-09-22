<?php

namespace Statamic\SeoPro\Content;

use Statamic\Contracts\Entries\Entry;
use Statamic\SeoPro\Content\Paths\ContentPathParser;
use Statamic\SeoPro\Contracts\TextProcessing\Links\FieldtypeLinkReplacer;

class LinkReplacer
{
    protected array $fieldtypeReplacers = [];

    public function __construct(
        protected readonly ContentMapper $contentMapper,
    ) {}

    protected function isValidReplacer(string $replacer): bool
    {
        return class_exists($replacer) && class_implements($replacer, FieldtypeLinkReplacer::class);
    }

    public function registerReplacer(string $replacer): static
    {
        if (! $this->isValidReplacer($replacer)) {
            return $this;
        }

        $this->fieldtypeReplacers[$replacer::fieldtype()] = $replacer;

        return $this;
    }

    /**
     * @param  string[]  $replacers
     * @return $this
     */
    public function registerReplacers(array $replacers): static
    {
        foreach ($replacers as $replacer) {
            $this->registerReplacer($replacer);
        }

        return $this;
    }

    protected function getReplacer(string $handle): ?FieldtypeLinkReplacer
    {
        $parsedPath = (new ContentPathParser)->parse($handle);
        $fieldtype = $parsedPath->getLastType();

        if (! array_key_exists($fieldtype, $this->fieldtypeReplacers)) {
            return null;
        }

        return app($this->fieldtypeReplacers[$fieldtype]);
    }

    protected function getReplacementContext(Entry $entry, LinkReplacement $replacement): ReplacementContext
    {
        $retrievedField = $this->contentMapper->retrieveField($entry, $replacement->fieldHandle);

        return new ReplacementContext(
            $entry,
            $replacement,
            $retrievedField,
        );
    }

    protected function withReplacer(Entry $entry, LinkReplacement $replacement, callable $callback): bool
    {
        if ($replacer = $this->getReplacer($replacement->fieldHandle)) {
            return $callback($replacer, $this->getReplacementContext($entry, $replacement));
        }

        return false;
    }

    public function canReplace(Entry $entry, LinkReplacement $replacement): bool
    {
        return $this->withReplacer($entry, $replacement, fn (FieldtypeLinkReplacer $replacer, ReplacementContext $context) => $replacer->canReplace($context));
    }

    public function replaceLink(Entry $entry, LinkReplacement $replacement): bool
    {
        if (! $replacement->getTarget()) {
            return false;
        }

        return $this->withReplacer($entry, $replacement, fn (FieldtypeLinkReplacer $replacer, ReplacementContext $context) => $replacer->replace($context));
    }
}
