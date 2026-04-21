<?php

namespace Statamic\SeoPro\Redirects;

use Illuminate\Http\Request;
use Statamic\SeoPro\Facades\Redirect as RedirectFacade;
use Statamic\Statamic;

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
            return;
        }

        $destinationUrl = $redirect->usesWildcard()
            ? WildcardUrlMatcher::resolveDestination($redirect->destinationUrl(), $captures)
            : $redirect->destinationUrl();

        if ($request->getQueryString() && config('statamic.seo-pro.redirects.preserve_query_string')) {
            $separator = str_contains($destinationUrl, '?') ? '&' : '?';
            $destinationUrl .= $separator.$request->getQueryString();
        }

        RecordRedirectHit::dispatch($redirect->id());

        return redirect($destinationUrl, $redirect->responseCode());
    }

    private function findExactMatch(string $path): ?Redirect
    {
        return RedirectFacade::query()
            ->where('source_url', $path)
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
            $matched = WildcardUrlMatcher::match($redirect->sourceUrl(), $path);

            if ($matched !== null) {
                $captures = $matched;

                return $redirect;
            }
        }

        return null;
    }
}
