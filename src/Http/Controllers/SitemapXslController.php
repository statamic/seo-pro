<?php

namespace Statamic\SeoPro\Http\Controllers;

use Illuminate\Support\Facades\File;

class SitemapXslController
{
    public function __invoke()
    {
        return response(
            content: File::get(__DIR__.'/../../../resources/views/generated/sitemap.xsl'),
            headers: ['Content-Type' => 'text/xsl']
        );
    }
}
