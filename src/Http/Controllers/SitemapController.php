<?php

namespace Statamic\SeoPro\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Statamic\SeoPro\Sitemap\Sitemap;

class SitemapController extends Controller
{
    public function show()
    {
        $cacheUntil = Carbon::now()->addMinutes(config('statamic.seo-pro.sitemap.expire'));

        $content = Cache::remember(Sitemap::CACHE_KEY, $cacheUntil, function () {
            return view('seo-pro::sitemap', [
                'pages' => Sitemap::pages(),
            ])->render();
        });

        return response($content)->header('Content-Type', 'text/xml');
    }
}
