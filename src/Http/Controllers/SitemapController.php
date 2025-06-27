<?php

namespace Statamic\SeoPro\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Statamic\Facades\URL;
use Statamic\SeoPro\Sitemap\Sitemap;

class SitemapController extends Controller
{
    public function index()
    {
        abort_unless(config('statamic.seo-pro.sitemap.enabled'), 404);

        if (config('statamic.seo-pro.sitemap.enforce_trailing_slashes')) {
            URL::enforceTrailingSlashes();
        }

        $cacheUntil = Carbon::now()->addMinutes(config('statamic.seo-pro.sitemap.expire'));

        if (config('statamic.seo-pro.sitemap.pagination.enabled', false)) {
            $content = Cache::remember(Sitemap::CACHE_KEY.'_index', $cacheUntil, function () {
                return view('seo-pro::sitemap_index', [
                    'xml_header' => '<?xml version="1.0" encoding="UTF-8"?>',
                    'sitemaps' => app(Sitemap::class)->paginatedSitemaps(),
                ])->render();
            });
        } else {
            $content = Cache::remember(Sitemap::CACHE_KEY, $cacheUntil, function () {
                return view('seo-pro::sitemap', [
                    'xml_header' => '<?xml version="1.0" encoding="UTF-8"?>',
                    'pages' => app(Sitemap::class)->pages(),
                ])->render();
            });
        }

        return response($content)->header('Content-Type', 'text/xml');
    }

    public function show($page)
    {
        abort_unless(config('statamic.seo-pro.sitemap.enabled'), 404);
        abort_unless(config('statamic.seo-pro.sitemap.pagination.enabled'), 404);
        abort_unless(filter_var($page, FILTER_VALIDATE_INT), 404);

        if (config('statamic.seo-pro.sitemap.enforce_trailing_slashes')) {
            URL::enforceTrailingSlashes();
        }

        $cacheUntil = Carbon::now()->addMinutes(config('statamic.seo-pro.sitemap.expire'));

        $cacheKey = Sitemap::CACHE_KEY.'_'.$page;

        $content = Cache::remember($cacheKey, $cacheUntil, function () use ($page) {
            abort_if(empty($pages = app(Sitemap::class)->paginatedPages($page)), 404);

            return view('seo-pro::sitemap', [
                'xml_header' => '<?xml version="1.0" encoding="UTF-8"?>',
                'pages' => $pages,
            ])->render();
        });

        return response($content)->header('Content-Type', 'text/xml');
    }
}
