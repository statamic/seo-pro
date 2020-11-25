<?php

namespace Statamic\SeoPro\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\SiteDefaults;
use Statamic\Support\Arr;

class SiteDefaultsController extends CpController
{
    public function edit()
    {
        $blueprint = SiteDefaults::blueprint();

        $fields = $blueprint
            ->fields()
            ->addValues(SiteDefaults::load()->all())
            ->preProcess();

        return view('seo-pro::edit', [
            'title' => __('Site Defaults'),
            'action' => cp_route('seo-pro.site-defaults.update'),
            'blueprint' => $blueprint->toPublishArray(),
            'meta' => $fields->meta(),
            'values' => $fields->values(),
        ]);
    }

    public function update(Request $request)
    {
        $blueprint = SiteDefaults::blueprint();

        $fields = $blueprint->fields()->addValues($request->all());

        $fields->validate();

        $values = Arr::removeNullValues($fields->process()->values()->all());

        SiteDefaults::load($values)->save();
    }
}
