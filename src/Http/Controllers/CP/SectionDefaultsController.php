<?php

namespace Statamic\SeoPro\Http\Controllers\CP;

use Inertia\Inertia;
use Statamic\Contracts\Entries\Collection;
use Statamic\Facades;
use Statamic\Http\Controllers\CP\CpController;

class SectionDefaultsController extends CpController
{
    public function index()
    {
        $this->authorize('edit seo section defaults');

        return Inertia::render('seo-pro::SectionDefaults/Index', [
            'collections' => Facades\Collection::all()
                ->sortBy('title')
                ->map(fn (Collection $collection): array => [
                    'title' => $collection->title(),
                    'handle' => $collection->handle(),
                    'icon' => $collection->icon(),
                ])
                ->values(),
            'taxonomies' => Facades\Taxonomy::all()->sortBy('title')->values(),
        ]);
    }
}
