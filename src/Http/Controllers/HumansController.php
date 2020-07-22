<?php

namespace Statamic\SeoPro\Http\Controllers;

use Illuminate\Routing\Controller;
use Statamic\SeoPro\Cascade;

class HumansController extends Controller
{
    public function show()
    {
        $cascade = (new Cascade)
            ->with(config('statamic.seo-pro.defaults'))
            // ->with($this->getSectionDefaults($this->data))
            ->get();

        $contents = view('seo-pro::humans', $cascade);

        $response = response()
            ->make($contents)
            ->header('Content-Type', 'text/plain');

        return $response;
    }
}
