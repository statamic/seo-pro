<?php

namespace Statamic\SeoPro\Http\Controllers\CP;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Site;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\Fields;
use Statamic\SeoPro\SectionDefaults\LocalizedSectionDefaults;
use Statamic\SeoPro\SectionDefaults\SectionDefaults;
use Statamic\Support\Arr;

abstract class BaseSectionDefaultsController extends CpController
{
    protected static $sectionType;

    abstract protected function getSectionItem($handle);

    public function edit(Request $request, $handle)
    {
        $this->authorize('edit seo section defaults');

        $sectionType = static::$sectionType;
        $site = $request->site ?? Site::selected()->handle();

        $item = $this->getSectionItem($handle);

        $localized = SectionDefaults::in($sectionType, $handle, $site);

        $blueprint = $this->blueprint();

        [$values, $meta] = $this->extractFromFields($localized, $blueprint);

        if ($hasOrigin = $localized->hasOrigin()) {
            [$originValues, $originMeta] = $this->extractFromFields($localized->origin(), $blueprint);
        }

        $viewData = [
            'blueprint' => $blueprint->toPublishArray(),
            'initialReference' => $localized->reference(),
            'initialValues' => $values,
            'initialMeta' => $meta,
            'initialEnabled' => $localized->isEnabled(),
            'initialLocalizations' => SectionDefaults::get($sectionType, $handle)->map(function (LocalizedSectionDefaults $loc) use ($localized, $sectionType, $handle): array {
                return [
                    'handle' => $loc->locale(),
                    'name' => $loc->site()->name(),
                    'active' => $loc->locale() === $localized->locale(),
                    'origin' => ! $loc->hasOrigin(),
                    'url' => cp_route("seo-pro.section-defaults.{$sectionType}.edit", array_filter([
                        $handle,
                        'site' => Site::multiEnabled() ? $loc->locale() : null,
                    ])),
                ];
            })->values()->all(),
            'initialLocalizedFields' => $localized->defaults()->keys()->all(),
            'initialHasOrigin' => $hasOrigin,
            'initialOriginValues' => $originValues ?? null,
            'initialOriginMeta' => $originMeta ?? null,
            'initialSite' => $site,
            'action' => cp_route("seo-pro.section-defaults.{$sectionType}.update", $handle),
            'title' => $item->title().' SEO',
            'configureUrl' => Site::multiEnabled()
                ? cp_route('seo-pro.section-defaults.configure.edit')
                : null,
        ];

        if ($request->wantsJson()) {
            return $viewData;
        }

        return Inertia::render('seo-pro::SectionDefaults/Edit', $viewData);
    }

    public function update($handle, Request $request)
    {
        $this->authorize('edit seo section defaults');

        $sectionType = static::$sectionType;
        $site = $request->site ?? Site::selected()->handle();

        $localized = SectionDefaults::in($sectionType, $handle, $site);
        $blueprint = $this->blueprint();

        $fields = $blueprint->fields()->addValues($request->all());
        $fields->validate();
        $values = collect($fields->process()->values());

        if ($localized->hasOrigin()) {
            $values = $values->only($request->input('_localized'));
        }

        $save = $this->saveSectionDefaults(
            type: $sectionType,
            handle: $handle,
            site: $site,
            values: Arr::removeNullValues($values->all()),
            localized: $localized,
        );

        if ($values->get('enabled') === false) {
            $this->removeChildSeo($this->getSectionItem($handle));
        }

        return ['saved' => $save];
    }

    protected function blueprint()
    {
        return Blueprint::make()->setContents([
            'tabs' => [
                'main' => [
                    'sections' => Fields::new()->getConfig(),
                ],
            ],
        ]);
    }

    protected function saveSectionDefaults(string $type, string $handle, string $site, array $values, LocalizedSectionDefaults $localized): bool
    {
        $values = collect($values);

        if ($values->get('enabled') === false) {
            SectionDefaults::disable($type, $handle);

            return true;
        }

        $localized->set($values->except('enabled')->all());

        return $localized->save();
    }

    private function extractFromFields(LocalizedSectionDefaults $defaults, \Statamic\Fields\Blueprint $blueprint): array
    {
        $values = collect();
        $target = $defaults;

        while ($target) {
            $values = $target->defaults()->merge($values);
            $target = $target->origin();
        }

        if (! $defaults->isEnabled()) {
            $values->put('enabled', false);
        }

        $fields = $blueprint
            ->fields()
            ->addValues($values->all())
            ->preProcess();

        return [$fields->values()->all(), $fields->meta()->all()];
    }

    protected function removeChildSeo($item)
    {
        if ($item instanceof Collection) {
            $this->removeChildEntrySeo($item);
        } elseif ($item instanceof Taxonomy) {
            $this->removeChildTermSeo($item);
        } else {
            return;
        }
    }

    protected function removeChildEntrySeo($collection)
    {
        $collection->queryEntries()->get()->filter->has('seo')->each(function ($entry) {
            $entry->remove('seo')->save();
        });
    }

    protected function removeChildTermSeo($taxonomy)
    {
        $taxonomy->queryTerms()->get()->filter->has('seo')->each(function ($term) {
            $term->data($term->data()->except('seo'))->save();
        });
    }
}
