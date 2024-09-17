<?php

namespace Statamic\SeoPro\TextProcessing\Content\Mappers;

use Statamic\Fieldtypes\Bard;
use Statamic\SeoPro\TextProcessing\Content\Mappers\Concerns\GetsSets;
use Stringy\StaticStringy as Stringy;

class BardFieldMapper extends AbstractFieldMapper
{
    use GetsSets;

    public static function fieldtype(): string
    {
        return Bard::handle();
    }

    public function getContent(): void
    {
        if (! is_array($this->value) || count($this->value) === 0) {
            return;
        }

        if (! array_key_exists('sets', $this->fieldConfig)) {
            $this->noSetContent();

            return;
        }

        $sets = $this->getSets();

        foreach ($this->value as $index => $value) {
            if (! array_key_exists('type', $value)) {
                continue;
            }
            $this->mapper->pushIndex($index);

            if ($value['type'] === 'paragraph') {
                $this->mapper->append('{node:paragraph}');
                $this->mapper->finish($this->getParagraphContent($value));
                $this->mapper->popIndex();
            } elseif ($value['type'] === 'set') {
                $setValues = $value['attrs']['values'] ?? null;

                if (! $setValues) {
                    continue;
                }

                if (! array_key_exists('type', $setValues)) {
                    continue;
                }

                if (! array_key_exists($setValues['type'], $sets)) {
                    continue;
                }

                $this->mapper->append('{set:'.$setValues['type'].'}');

                $set = $sets[$setValues['type']];
                $setFields = collect($set['fields'])->keyBy('handle')->all();
                $setValues = collect($setValues)->except(['type'])->all();
                $mapper = $this->mapper->newMapper();

                $mappedContent = $mapper->getContentMappingFromArray($setFields, $setValues);
                $currentPath = $this->mapper->getPath();

                foreach ($mappedContent as $mappedPath => $mappedValue) {
                    $this->mapper->addMapping($currentPath.$mappedPath, $mappedValue);
                }

                $this->mapper->popIndex();
            }
        }
    }

    protected function noSetContent(): void
    {
        $content = '';

        foreach ($this->value as $value) {
            $content .= $this->getParagraphContent($value);
        }

        $this->mapper->finish(Stringy::collapseWhitespace($content));
    }

    protected function getParagraphContent(array $value): string
    {
        $content = '';

        if (! array_key_exists('content', $value)) {
            return $content;
        }

        foreach ($value['content'] as $contentValue) {
            if (! array_key_exists('type', $contentValue)) {
                continue;
            }

            if ($contentValue['type'] === 'text') {
                $content .= $contentValue['text'];
            }
        }

        return $content;
    }
}
