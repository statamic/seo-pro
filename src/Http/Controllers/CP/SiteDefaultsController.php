<?php

namespace Statamic\SeoPro\Http\Controllers\CP;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Statamic\Facades\Site;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\SiteDefaults\SiteDefaults;

class SiteDefaultsController extends CpController
{
    public function edit()
    {
        $site = $request->site ?? Site::selected()->handle();

        $this->authorize('edit seo site defaults');

        $siteDefaults = SiteDefaults::in($site);
        $blueprint = $siteDefaults->blueprint();

        $fields = $blueprint
            ->fields()
            ->addValues($siteDefaults->all())
            ->preProcess();

        // todo: origin values/meta


        return Inertia::render('seo-pro::SiteDefaults/Edit', [
            'blueprint' => $blueprint->toPublishArray(),
            'initialValues' => $fields->values(),
            'initialMeta' => $fields->meta(),
            'initialLocalizations' => Site::all()->map(function ($localized) use ($site) {
                return [
                    'handle' => $localized->handle(),
                    'name' => $localized->name(),
                    'active' => $localized->handle() === $site,
                    'origin' => Site::default()->handle() === $localized->handle(),
                    'url' => cp_route('seo-pro.site-defaults.edit'),
                ];
            })->values()->all(),
            'initialLocalizedFields' => [],
            'initialHasOrigin' => false,
            'initialOriginValues' => [],
            'initialOriginMeta' => [],
            'initialSite' => $site,
            'action' => cp_route('seo-pro.site-defaults.update'),
        ]);
    }

    public function update(Request $request)
    {
        $this->authorize('edit seo site defaults');

        // todo: $site

        $fields = SiteDefaults::in($site)->blueprint()->fields()->addValues($request->all());

        $fields->validate();

        $values = $fields->process()->values()->all();

        SiteDefaults::in($site)->set($values)->save();
    }
}
