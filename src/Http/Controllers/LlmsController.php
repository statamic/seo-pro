<?php

namespace Statamic\SeoPro\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Statamic\Facades\Site;
use Statamic\SeoPro\Llms\Llms;
use Statamic\SeoPro\Llms\LlmsRenderCache;
use Statamic\StaticCaching\Cacher;
use Statamic\StaticCaching\Cachers\FileCacher;

class LlmsController extends Controller
{
    public function __construct(private LlmsRenderCache $cache) {}

    public function show(Request $request)
    {
        $site = Site::get($request->route('seoProSite'));

        abort_unless($site && Llms::url($site) === rtrim($request->url(), '/'), 404);

        $document = Llms::get($site);

        abort_unless($document->enabled(), 404);

        $contents = $this->cache->get($document, $site);
        $response = response($contents)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('ETag', '"'.hash('sha256', $contents).'"')
            ->setPublic();

        if ($request->headers->get('If-None-Match') === $response->headers->get('ETag')) {
            $response->setNotModified();
        }

        // Statamic's full-measure cacher stores all routes as .html and its standard
        // rewrite rules serve them as text/html. Our render cache still prevents the
        // document from being rebuilt on each request while preserving the correct type.
        if (app(Cacher::class) instanceof FileCacher) {
            $response->headers->set('X-Statamic-Uncacheable', 'true');
        }

        return $response;
    }
}
