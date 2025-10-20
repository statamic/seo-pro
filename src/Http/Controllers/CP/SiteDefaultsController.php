<?php

namespace Statamic\SeoPro\Http\Controllers\CP;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\SiteDefaults;

class SiteDefaultsController extends CpController
{
    public function edit()
    {
        $this->authorize('edit seo site defaults');

        $siteDefaults = SiteDefaults::load();
        $blueprint = $siteDefaults->blueprint();

        $fields = $blueprint
            ->fields()
            ->addValues($siteDefaults->all())
            ->preProcess();

        return Inertia::render('seo-pro::SiteDefaults/Edit', [
            'blueprint' => $blueprint->toPublishArray(),
            'initialValues' => $fields->values(),
            'initialMeta' => $fields->meta(),
            'action' => cp_route('seo-pro.site-defaults.update'),
        ]);
    }

    public function update(Request $request)
    {
        $this->authorize('edit seo site defaults');

        $fields = SiteDefaults::load()->blueprint()->fields()->addValues($request->all());

        $fields->validate();

        $values = $fields->process()->values()->all();

        SiteDefaults::load($values)->save();
    }
}
