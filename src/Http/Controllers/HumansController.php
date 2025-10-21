<?php

namespace Statamic\SeoPro\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Statamic\Facades;
use Statamic\SeoPro\Cascade;
use Statamic\SeoPro\SiteDefaults\SiteDefaults;
use Statamic\Sites\Site;

class HumansController extends Controller
{
    public function show()
    {
        abort_unless(config('statamic.seo-pro.humans.enabled'), 404);

        $site = Facades\Site::all()->first(fn (Site $site) => Str::of($site->absoluteUrl())->startsWith(request()->schemeAndHttpHost()));

        $cascade = (new Cascade)
            ->with(SiteDefaults::in($site->handle())->all())
            ->get();

        $contents = view('seo-pro::humans', $cascade);

        return response()
            ->make($contents)
            ->header('Content-Type', 'text/plain');
    }
}
