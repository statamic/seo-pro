<?php

namespace Statamic\SeoPro\Http\Controllers;

use Illuminate\Routing\Controller;
use Statamic\Facades\Site;
use Statamic\SeoPro\Cascade;
use Statamic\SeoPro\SiteDefaults\SiteDefaults;

class HumansController extends Controller
{
    public function show()
    {
        abort_unless(config('statamic.seo-pro.humans.enabled'), 404);

        $cascade = (new Cascade)
            ->with(SiteDefaults::in(Site::current()->handle())->all())
            ->get();

        $contents = view('seo-pro::humans', $cascade);

        return response()
            ->make($contents)
            ->header('Content-Type', 'text/plain');
    }
}
