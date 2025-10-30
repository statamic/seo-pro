<?php

namespace Statamic\SeoPro\Fieldtypes;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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
            'assetContainerUrl' => config('statamic.seo-pro.assets.container')
                ? AssetContainer::find(config('statamic.seo-pro.assets.container'))->url()
                : null,
            'faviconUrl' => $this->faviconUrl(),
        ];
    }

    private function getRouteString(): string
    {
        $item = $this->field->parent();

        $route = match (true) {
            $item instanceof \Statamic\Contracts\Entries\Entry => $item->route(),
            $item instanceof \Statamic\Contracts\Entries\Collection => $item->route(Site::selected()->handle()),
            $item instanceof \Statamic\Contracts\Taxonomies\Taxonomy, $item instanceof \Statamic\Contracts\Taxonomies\Term => '{{ slug }}',
            default => '',
        };

        return Str::of($route)
            ->replaceMatches('/(?<!\{)\{(?!\{)|(?<!\})\}(?!\})/', '$0$0')
            ->toString();
    }

    private function faviconUrl(): ?string
    {
        $domain = parse_url($this->field->parent()?->absoluteUrl() ?? Site::selected()->url(), PHP_URL_HOST);

        return Cache::rememberForever("seo-pro::favicon.{$domain}", function () use ($domain) {
            $url = "https://www.google.com/s2/favicons?domain={$domain}";

            try {
                $response = Http::head($url);

                if ($response->ok()) {
                    return $url;
                }
            } catch (\Exception $e) {
                //
            }

            return null;
        });
    }
}
