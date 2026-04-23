<?php

namespace Statamic\SeoPro\Redirects;

use Illuminate\Http\Request;
use Statamic\Facades\Data;
use Statamic\Facades\Site;
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
        $site = Site::findByUrl($request->getUri()) ?? Site::default();
        $path = $this->siteRelativePath($request->getPathInfo(), $site);
        $redirect = $this->findExactMatch($path, $site->handle()) ?? $this->findWildcardMatch($path, $site->handle(), $captures);

        if (! $redirect) {
            if (config('statamic.seo-pro.redirects.errors.enabled')) {
                RecordError::dispatch($path, $site->handle());
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

    private function findExactMatch(string $path, string $siteHandle): ?Redirect
    {
        return RedirectFacade::query()
            ->where('source', $path)
            ->where('site', $siteHandle)
            ->where('enabled', true)
            ->first();
    }

    private function findWildcardMatch(string $path, string $siteHandle, array &$captures): ?Redirect
    {
        $wildcardRedirects = RedirectFacade::query()
            ->where('site', $siteHandle)
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

    private function siteRelativePath(string $requestPath, \Statamic\Sites\Site $site): string
    {
        $sitePrefix = rtrim(parse_url($site->url(), PHP_URL_PATH) ?? '', '/');

        if ($sitePrefix && Str::startsWith($requestPath, $sitePrefix)) {
            $requestPath = Str::removeLeft($requestPath, $sitePrefix);
        }

        return $requestPath ?: '/';
    }
}
