<?php

namespace Statamic\SeoPro\Redirects;

use Illuminate\Http\Request;
use Statamic\Facades\Data;
use Statamic\SeoPro\Facades\Error;
use Statamic\SeoPro\Facades\Redirect as RedirectFacade;
use Statamic\Statamic;
use Statamic\Support\Str;

class HandleRedirects
{
    public function __invoke(Request $request)
    {
        if (Statamic::isCpRoute() || Statamic::isApiRoute()) {
            return;
        }

        $captures = [];
        $path = $request->getPathInfo();

        $redirect = $this->findExactMatch($path) ?? $this->findWildcardMatch($path, $captures);

        if (! $redirect) {
            if (config('statamic.seo-pro.redirects.errors.enabled')) {
                RecordError::dispatch($path);
            }

            return;
        }

        $destination = $redirect->usesWildcard()
            ? WildcardUrlMatcher::resolveDestination($redirect->destination(), $captures)
            : $redirect->destination();

        if (Str::startsWith($destination, 'entry::') && $data = Data::find($destination)) {
            $destination = $data->absoluteUrl();
        }

        if ($request->getQueryString() && config('statamic.seo-pro.redirects.preserve_query_string')) {
            $separator = str_contains($destination, '?') ? '&' : '?';
            $destination .= $separator.$request->getQueryString();
        }

        RecordRedirectHit::dispatch($redirect->id());

        return redirect($destination, $redirect->responseCode());
    }

    private function findExactMatch(string $path): ?Redirect
    {
        return RedirectFacade::query()
            ->where('source', $path)
            ->where('enabled', true)
            ->first();
    }

    private function findWildcardMatch(string $path, array &$captures): ?Redirect
    {
        $wildcardRedirects = RedirectFacade::query()
            ->where('enabled', true)
            ->get()
            ->filter->usesWildcard();

        foreach ($wildcardRedirects as $redirect) {
            $matched = WildcardUrlMatcher::match($redirect->source(), $path);

            if ($matched !== null) {
                $captures = $matched;

                return $redirect;
            }
        }

        return null;
    }
}
