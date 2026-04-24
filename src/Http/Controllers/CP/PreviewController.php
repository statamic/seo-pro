<?php

namespace Statamic\SeoPro\Http\Controllers\CP;

use Illuminate\Http\Request;
use Statamic\Facades\Asset;
use Statamic\Facades\Blink;
use Statamic\Facades\Data;
use Statamic\Facades\Image;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Statamic;
use Statamic\Support\Arr;

class PreviewController extends CpController
{
    public function __invoke(Request $request)
    {
        $item = Data::find($request->id);

        foreach ($request->values as $key => $value) {
            if ($key === 'slug') {
                $item->slug($value);

                continue;
            }

            $item->setSupplement($key, $value);
        }

        Blink::store('entry-uris')->forget($item->id());

        return [
            'url' => $item->absoluteUrl(),
            'og_image_url' => $request->image ? $this->transformImage('seo_pro_og', $request->image) : null,
            'twitter_image_url' => $request->image ? $this->transformImage('seo_pro_twitter', $request->image) : null,
        ];
    }

    private function transformImage(string $preset, string $assetId): ?string
    {
        $asset = Asset::find($assetId);

        if (array_key_exists($preset, Image::customManipulationPresets())) {
            $glide = Statamic::tag('glide:generate')
                ->src($asset)
                ->preset($preset)
                ->absolute(true)
                ->fetch();

            return Arr::get($glide, '0.url');
        }

        return $asset->absoluteUrl();
    }
}
