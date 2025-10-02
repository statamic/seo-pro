<?php

namespace Statamic\SeoPro\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Statamic\Facades;
use Statamic\SeoPro\Sitemap\Sitemap;
use Statamic\Sites\Site;

class SitemapController extends Controller
{
    public function index()
    {
        abort_unless(config('statamic.seo-pro.sitemap.enabled'), 404);

        $key = request()->getHttpHost();
        $cacheUntil = Carbon::now()->addMinutes(config('statamic.seo-pro.sitemap.expire'));
        $sites = Facades\Site::all()->filter(fn (Site $site) => Str::of($site->absoluteUrl())->startsWith(request()->schemeAndHttpHost()));

        $key = request()->getHttpHost();

        if (config('statamic.seo-pro.sitemap.pagination.enabled', false)) {
            $content = Cache::remember(Sitemap::CACHE_KEY.'_'.$key.'_index', $cacheUntil, function () use ($sites) {
                return view('seo-pro::sitemap_index', [
                    'xml_header' => '<?xml version="1.0" encoding="UTF-8"?>',
                    'sitemaps' => app(Sitemap::class)->forSites($sites)->paginatedSitemaps(),
                ])->render();
            });
        } else {
            $content = Cache::remember(Sitemap::CACHE_KEY.'_'.$key, $cacheUntil, function () use ($sites) {
                return view('seo-pro::sitemap', [
                    'xml_header' => '<?xml version="1.0" encoding="UTF-8"?>',
                    'pages' => app(Sitemap::class)->forSites($sites)->pages(),
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
