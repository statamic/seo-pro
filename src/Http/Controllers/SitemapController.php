<?php

namespace Statamic\SeoPro\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Statamic\SeoPro\Sitemap\Sitemap;

class SitemapController extends Controller
{
    public function show($page = null)
    {
        abort_unless(config('statamic.seo-pro.sitemap.enabled'), 404);

        $cacheUntil = Carbon::now()->addMinutes(config('statamic.seo-pro.sitemap.expire'));
        $cacheKey = Sitemap::CACHE_KEY;

        if (config('statamic.seo-pro.sitemap.paginated', false)) {
            if ($page !== null) {
                if (! filter_var($page, FILTER_VALIDATE_INT)) {
                    abort(404);
                }

                $cacheKey .= '_'.$page;
            }
        }

        $content = Cache::remember($cacheKey, $cacheUntil, function () use ($page) {
            $data = [
                'xml_header' => '<?xml version="1.0" encoding="UTF-8"?>',
            ];

            $view = 'seo-pro::sitemap';

            if (! config('statamic.seo-pro.sitemap.paginated', false)) {
                $data['pages'] = Sitemap::pages();
            }

            if (config('statamic.seo-pro.sitemap.paginated', false)) {
                if ($page === null) {
                    $data['sitemaps'] = Sitemap::paginatedSitemaps();
                    $view = 'seo-pro::sitemap_index';
                } else {
                    $data['pages'] = Sitemap::paginatedPages($page);

                    if (empty($data['pages'])) {
                        abort(404);
                    }
                }
            }

            return view($view, $data)->render();
        });

        return response($content)->header('Content-Type', 'text/xml');
    }
}
