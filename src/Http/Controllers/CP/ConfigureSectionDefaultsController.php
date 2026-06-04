<?php

namespace Statamic\SeoPro\Http\Controllers\CP;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Site;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\SectionDefaults\SectionDefaults;

class ConfigureSectionDefaultsController extends CpController
{
    public function edit(Request $request)
    {
        $this->authorize('edit seo section defaults');

        throw_unless(Site::hasMultiple(), NotFoundHttpException::class);

        return [
            'sites' => Site::all()->map(function ($site) {
                return [
                    'name' => $site->name(),
                    'handle' => $site->handle(),
                    'origin' => SectionDefaults::origins()->get($site->handle()),
                ];
            })->values(),
        ];
    }

    public function update(Request $request)
    {
        $this->authorize('edit seo section defaults');

        throw_unless(Site::hasMultiple(), NotFoundHttpException::class);

        $request->validate([
            'sites' => ['required', 'array', 'min:1'],
            'sites.*.handle' => ['required', 'string'],
            'sites.*.origin' => ['nullable', 'string'],
        ]);

        if ($request->collect('sites')->map->origin->filter()->count() == count($request->sites)) {
            throw ValidationException::withMessages([
                'sites' => __('statamic::validation.one_site_without_origin'),
            ]);
        }

        $sites = $request->collect('sites')
            ->mapWithKeys(fn ($site) => [$site['handle'] => $site['origin']])
            ->all();

        SectionDefaults::origins($sites);
    }
}
