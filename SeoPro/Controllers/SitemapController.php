<?php

namespace Statamic\Addons\SeoPro\Controllers;

use Illuminate\Support\Facades\Cache;
use Statamic\Addons\SeoPro\Sitemap\Sitemap;

class SitemapController extends Controller
{
    public function show()
    {
        $content = Cache::remember(Sitemap::CACHE_KEY, $this->getConfig('sitemap_cache_length'), function () {
            return $this->view('sitemap', [
                'xmlHeader' => '<?xml version="1.0" encoding="UTF-8"?>',
                'sitemap' => new Sitemap,
            ])->render();
        });

        return response($content)->header('Content-Type', 'text/xml');
    }
}
