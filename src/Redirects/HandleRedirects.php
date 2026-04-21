<?php

namespace Statamic\SeoPro\Redirects;

use Illuminate\Http\Request;
use Statamic\SeoPro\Facades\Redirect;
use Statamic\Statamic;

class HandleRedirects
{
    public function __invoke(Request $request)
    {
        if (Statamic::isCpRoute() || Statamic::isApiRoute()) {
            return;
        }

        $redirect = Redirect::query()
            ->where('source_url', $request->getPathInfo())
            ->where('enabled', true)
            ->first();

        if (! $redirect) {
            return;
        }

        $destinationUrl = $redirect->destinationUrl();

        if ($request->getQueryString() && config('statamic.seo-pro.redirects.preserve_query_string')) {
            $separator = str_contains($destinationUrl, '?') ? '&' : '?';
            $destinationUrl .= $separator.$request->getQueryString();
        }

        RecordRedirectHit::dispatch($redirect->id());

        return redirect($destinationUrl, $redirect->responseCode());
    }
}
