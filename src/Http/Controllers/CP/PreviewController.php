<?php

namespace Statamic\SeoPro\Http\Controllers\CP;

use Illuminate\Http\Request;
use Statamic\Facades\Blink;
use Statamic\Facades\Data;
use Statamic\Http\Controllers\CP\CpController;

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
        ];
    }
}
