<?php

namespace Statamic\SeoPro\Http\Controllers\CP;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Statamic\Facades\Site;
use Statamic\Fields\Blueprint;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\SiteDefaults\LocalizedSiteDefaults;
use Statamic\SeoPro\SiteDefaults\SiteDefaults;
use Statamic\Support\Arr;

class SiteDefaultsController extends CpController
{
    public function edit(Request $request)
    {
        $this->authorize('edit seo site defaults');

        $site = $request->site ?? Site::selected()->handle();

        $siteDefaults = SiteDefaults::in($site);
        $blueprint = $siteDefaults->blueprint();

        [$values, $meta] = $this->extractFromFields($siteDefaults, $blueprint);

        if ($hasOrigin = $siteDefaults->hasOrigin()) {
            [$originValues, $originMeta] = $this->extractFromFields($siteDefaults->origin(), $blueprint);
        }

        $viewData = [
            'blueprint' => $blueprint->toPublishArray(),
            'initialReference' => $siteDefaults->reference(),
            'initialValues' => $values,
            'initialMeta' => $meta,
            'initialLocalizations' => SiteDefaults::get()->map(function ($localized) use ($siteDefaults): array {
                return [
                    'handle' => $localized->locale(),
                    'name' => $localized->site()->name(),
                    'active' => $localized->locale() === $siteDefaults->locale(),
                    'origin' => ! $localized->hasOrigin(),
                    'url' => $localized->editUrl(),
                ];
            })->values()->all(),
            'initialLocalizedFields' => $siteDefaults->defaults()->keys()->all(),
            'initialHasOrigin' => $hasOrigin,
            'initialOriginValues' => $originValues ?? null,
            'initialOriginMeta' => $originMeta ?? null,
            'initialSite' => $site,
            'action' => cp_route('seo-pro.site-defaults.update'),
        ];

        if ($request->wantsJson()) {
            return $viewData;
        }

        return Inertia::render('seo-pro::SiteDefaults/Edit', $viewData);
    }

    public function update(Request $request)
    {
        $this->authorize('edit seo site defaults');

        $site = $request->site ?? Site::selected()->handle();

        $siteDefaults = SiteDefaults::in($site);

        $fields = $siteDefaults->blueprint()->fields()->addValues($request->all());

        $fields->validate();

        $values = $fields->process()->values();

        if ($siteDefaults->hasOrigin()) {
            $values = $values->only($request->input('_localized'));
        }

        $siteDefaults->set(Arr::removeNullValues($values->all()));

        $save = $siteDefaults->save();

        return ['saved' => $save];
    }

    private function extractFromFields(LocalizedSiteDefaults $defaults, Blueprint $blueprint): array
    {
        $values = collect();
        $target = $defaults;

        while ($target) {
            $values = $target->defaults()->merge($values);
            $target = $target->origin();
        }

        $values = $values->all();

        $fields = $blueprint
            ->fields()
            ->addValues($values)
            ->preProcess();

        return [$fields->values()->all(), $fields->meta()->all()];
    }
}
