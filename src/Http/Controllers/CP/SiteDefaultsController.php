<?php

namespace Statamic\SeoPro\Http\Controllers\CP;

use Illuminate\Http\Request;
use Statamic\CP\PublishForm;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\SiteDefaults;

class SiteDefaultsController extends CpController
{
    public function edit()
    {
        $this->authorize('edit seo site defaults');

        $siteDefaults = SiteDefaults::load();

        return PublishForm::make($siteDefaults->blueprint())
            ->asConfig()
            ->icon('earth')
            ->title(__('seo-pro::messages.site_defaults'))
            ->values($siteDefaults->all())
            ->submittingTo(cp_route('seo-pro.site-defaults.update'));
    }

    public function update(Request $request)
    {
        $this->authorize('edit seo site defaults');

        $values = PublishForm::make(SiteDefaults::load()->blueprint())->submit($request->all());

        SiteDefaults::load($values)->save();
    }
}
