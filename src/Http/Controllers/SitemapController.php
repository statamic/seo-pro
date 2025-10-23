<?php

namespace Statamic\SeoPro\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Statamic\SeoPro\Sitemap\Sitemap;

class SitemapController extends Controller
{
    public function __construct(public Sitemap $sitemap)
    {}

    public function index()
    {
        abort_unless(config('statamic.seo-pro.sitemap.enabled'), 404);

        $cacheUntil = Carbon::now()->addMinutes(config('statamic.seo-pro.sitemap.expire'));
        $cacheKey = join('_', [Sitemap::CACHE_KEY, request()->getHttpHost(), $this->sitemap->sites()->map->handle()->join('')]);

        if (config('statamic.seo-pro.sitemap.pagination.enabled', false)) {
            $content = Cache::remember("{$cacheKey}_index", $cacheUntil, function () {
                return view('seo-pro::sitemap_index', [
                    'xml_header' => '<?xml version="1.0" encoding="UTF-8"?>',
                    'sitemaps' => $this->sitemap->paginatedSitemaps(),
                ])->render();
            });
        } else {
            $content = Cache::remember($cacheKey, $cacheUntil, function () {
                return view('seo-pro::sitemap', [
                    'xml_header' => '<?xml version="1.0" encoding="UTF-8"?>',
                    'pages' => $this->sitemap->pages(),
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
            abort_if(empty($pages = $this->sitemap->paginatedPages($page)), 404);

            return view('seo-pro::sitemap', [
                'xml_header' => '<?xml version="1.0" encoding="UTF-8"?>',
                'pages' => $pages,
            ])->render();
        });

        return response($content)->header('Content-Type', 'text/xml');
    }
}
