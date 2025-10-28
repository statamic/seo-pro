<?php

namespace Statamic\SeoPro\Fieldtypes;

use Illuminate\Support\Str;
use Statamic\Facades\Antlers;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Site;
use Statamic\Fields\Fieldtype;

class SeoProPreviews extends Fieldtype
{
    public $selectable = false;

    public function preload()
    {
        return [
            'initialUrl' => $this->field->parent()?->absoluteUrl(),
            'routeFields' => Antlers::identifiers($this->getRouteString()),
            'previewUrl' => cp_route('seo-pro.preview'),
            'assetContainerUrl' => AssetContainer::find(config('statamic.seo-pro.assets.container'))->url(),
        ];
    }

    private function getRouteString(): string
    {
        $item = $this->field->parent();

        $route = match (true) {
            $item instanceof \Statamic\Contracts\Entries\Entry => $item->route(),
            $item instanceof \Statamic\Contracts\Entries\Collection => $item->route(Site::selected()->handle()),
            $item instanceof \Statamic\Contracts\Taxonomies\Taxonomy, $item instanceof \Statamic\Contracts\Taxonomies\Term => '{{ slug }}',
        };

        return Str::of($route)
            ->replaceMatches('/(?<!\{)\{(?!\{)|(?<!\})\}(?!\})/', '$0$0')
            ->toString();
    }
}
