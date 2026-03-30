<?php

namespace Statamic\SeoPro\Fieldtypes\Concerns;

use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Blink;
use Statamic\SeoPro\Cascade;
use Statamic\SeoPro\GetsSectionDefaults;
use Statamic\SeoPro\SiteDefaults\SiteDefaults;

trait ResolvesPlaceholders
{
    use GetsSectionDefaults;

    private function getPlaceholders(): array
    {
        $parent = $this->field()?->parent();

        if ($parent instanceof Entry || $parent instanceof Term) {
            return $this->getEntryPlaceholders($parent);
        }

        if ($this->isOnSectionDefaultsRoute()) {
            return $this->getSectionDefaultsPlaceholders();
        }

        return [];
    }

    private function getEntryPlaceholders(Entry|Term $parent): array
    {
        return Blink::once('seo-pro::placeholders::'.$parent->id(), function () use ($parent) {
            $cascade = (new Cascade)
                ->withSiteDefaults(SiteDefaults::in($parent->locale())->all())
                ->withSectionDefaults($this->getSectionDefaults($parent))
                ->with($parent->value('seo', []))
                ->withCurrent($parent)
                ->get();

            return collect($cascade)->map(function ($value) {
                if ($value instanceof \Statamic\Assets\Asset) {
                    return $value->path();
                }

                if ($value instanceof \Illuminate\Support\Collection) {
                    return $value->join("\n");
                }

                if (is_array($value) && is_string(array_first($value))) {
                    return implode(', ', $value);
                }

                return $value;
            })->all();
        });
    }

    private function getSectionDefaultsPlaceholders(): array
    {
        $siteDefaults = SiteDefaults::in(request()->route('site') ?? 'default');

        return $siteDefaults?->values()->all() ?? [];
    }

    private function isOnSectionDefaultsRoute(): bool
    {
        $routeName = request()->route()?->getName() ?? '';

        return str_contains($routeName, 'seo-pro.section-defaults.');
    }
}
