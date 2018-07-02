<?php

namespace Statamic\Addons\SeoPro\Controllers;

use Statamic\Addons\SeoPro\Sitemap\Sitemap;

class SitemapController extends Controller
{
    public function show()
    {
        $content = $this->view('sitemap', [
            'sitemap' => new Sitemap
        ]);

        return response($content)->header('Content-Type', 'text/xml');
    }
}
