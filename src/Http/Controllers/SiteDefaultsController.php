<?php

namespace Statamic\SeoPro\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\CP\PublishForm;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\SiteDefaults;

class SiteDefaultsController extends CpController
{
    public function edit()
    {
        abort_unless(User::current()->can('edit seo site defaults'), 403);

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
        abort_unless(User::current()->can('edit seo site defaults'), 403);

        $values = PublishForm::make(SiteDefaults::load()->blueprint())->submit($request->all());

        SiteDefaults::load($values)->save();
    }
}
