<?php

namespace Statamic\SeoPro\Fieldtypes;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Antlers;
use Statamic\Facades\Site;
use Statamic\Fields\Fieldtype;
use Statamic\SeoPro\Fieldtypes\Concerns\ResolvesPlaceholders;

class SeoProPreviews extends Fieldtype
{
    use ResolvesPlaceholders;

    public $selectable = false;

    public function preload()
    {
        return [
            'initialUrl' => $this->field->parent()?->absoluteUrl(),
            'routeFields' => Antlers::identifiers($this->getRouteString()),
            'previewUrl' => cp_route('seo-pro.preview'),
            'faviconUrl' => $this->faviconUrl(),
            'placeholders' => $this->getPlaceholders(),
        ];
    }

    private function getRouteString(): string
    {
        $item = $this->field->parent();

        $route = match (true) {
            $item instanceof Entry => $item->route(),
            $item instanceof Collection => $item->route(Site::selected()->handle()),
            $item instanceof Taxonomy, $item instanceof Term => '{{ slug }}',
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
