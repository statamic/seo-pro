<?php

namespace Statamic\SeoPro\Http\Controllers;

use Illuminate\Routing\Controller;
use Statamic\SeoPro\Cascade;
use Statamic\SeoPro\SiteDefaults\SiteDefaults;

class HumansController extends Controller
{
    public function show()
    {
        abort_unless(config('statamic.seo-pro.humans.enabled'), 404);

        $cascade = (new Cascade)
            ->with(SiteDefaults::get()->first()->all()) // todo: this should really get the first site on this domain
            ->get();

        $contents = view('seo-pro::humans', $cascade);

        $response = response()
            ->make($contents)
            ->header('Content-Type', 'text/plain');

        return $response;
    }
}
