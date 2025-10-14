<?php

namespace Statamic\SeoPro\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\CP\PublishForm;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\SiteDefaults;
use Statamic\Support\Arr;

class SiteDefaultsController extends CpController
{
    public function edit()
    {
        abort_unless(User::current()->can('edit seo site defaults'), 403);

        $siteDefaults = SiteDefaults::load();

        return PublishForm::make($siteDefaults->blueprint())
            ->asConfig()
            ->title(__('seo-pro::messages.site_defaults'))
            ->values($siteDefaults->all())
            ->submittingTo(cp_route('seo-pro.site-defaults.update'));
    }

    public function update(Request $request)
    {
        abort_unless(User::current()->can('edit seo site defaults'), 403);

        $blueprint = SiteDefaults::load()->blueprint();

        $fields = $blueprint->fields()->addValues($request->all());

        $fields->validate();

        $values = Arr::removeNullValues($fields->process()->values()->all());

        SiteDefaults::load($values)->save();
    }
}
