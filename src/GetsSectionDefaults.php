<?php

namespace Statamic\SeoPro;

use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Blink;
use Statamic\Facades\Blueprint;
use Statamic\SeoPro\SectionDefaults\SectionDefaults;
use Statamic\Taxonomies\LocalizedTerm;

trait GetsSectionDefaults
{
    public function getSectionDefaults($current)
    {
        if (! $parent = $this->getSectionParent($current)) {
            return [];
        }

        $type = $this->getSectionType($parent);
        $handle = $parent->handle();
        $locale = method_exists($current, 'locale') ? $current->locale() : null;

        if (! $locale) {
            return [];
        }

        $localized = SectionDefaults::in($type, $handle, $locale);

        if (! $localized || ! $localized->isEnabled()) {
            return false;
        }

        return Blink::once($this->getCacheKey($type, $handle, $locale), fn () => $localized->values()->all());
    }

    public function getAugmentedSectionDefaults($current)
    {
        if (! $parent = $this->getSectionParent($current)) {
            return [];
        }

        $type = $this->getSectionType($parent);
        $handle = $parent->handle();
        $locale = method_exists($current, 'locale') ? $current->locale() : null;

        if (! $locale) {
            return [];
        }

        $localized = SectionDefaults::in($type, $handle, $locale);

        if (! $localized || ! $localized->isEnabled()) {
            return [];
        }

        $seo = $localized->values()->all();

        if (empty($seo)) {
            return [];
        }

        return Blink::once($this->getCacheKey($type, $handle, $locale).'.augmented', fn () => Blueprint::make()
            ->setContents([
                'tabs' => [
                    'main' => [
                        'sections' => Fields::new()->getConfig(),
                    ],
                ],
            ])
            ->fields()
            ->addValues($seo)
            ->augment()
            ->values()
            ->only(array_keys($seo))
            ->all()
        );
    }

    protected function getCacheKey(string $type, string $handle, string $locale): string
    {
        return "seo-pro.section-defaults.{$type}::{$handle}::{$locale}";
    }

    protected function getSectionParent($current)
    {
        if ($current instanceof Entry) {
            return $current->collection();
        } elseif ($current instanceof Term || $current instanceof LocalizedTerm) {
            return $current->taxonomy();
        }

        return null;
    }

    protected function getSectionType($parent): string
    {
        return $parent instanceof Collection
            ? 'collections'
            : 'taxonomies';
    }
}
