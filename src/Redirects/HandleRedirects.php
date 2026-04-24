<?php

namespace Statamic\SeoPro\Redirects;

use Illuminate\Http\Request;
use Statamic\Facades\Data;
use Statamic\Facades\Site;
use Statamic\SeoPro\Facades\Redirect as RedirectFacade;
use Statamic\Sites\Site as SiteInstance;
use Statamic\Statamic;
use Statamic\Support\Str;

class HandleRedirects
{
    public function __invoke(Request $request)
    {
        if (Statamic::isCpRoute() || Statamic::isApiRoute()) {
            return;
        }

        $site = Site::findByUrl($request->getUri()) ?? Site::default();
        $path = $this->stripSitePrefix($request->getPathInfo(), $site);

        if (! $redirect = $this->findRedirect($path, $site->handle())) {
            $this->recordError($path, $site->handle());

            return;
        }

        RecordRedirectHit::dispatch($redirect->id());

        return redirect(
            $this->resolveDestination($redirect, $path, $request),
            $redirect->responseCode(),
        );
    }

    private function stripSitePrefix(string $requestPath, SiteInstance $site): string
    {
        $sitePrefix = rtrim(parse_url($site->url(), PHP_URL_PATH) ?? '', '/');

        if ($sitePrefix && Str::startsWith($requestPath, $sitePrefix)) {
            $requestPath = Str::removeLeft($requestPath, $sitePrefix);
        }

        return $requestPath ?: '/';
    }

    private function findRedirect(string $path, string $siteHandle): ?Redirect
    {
        return $this->findExactMatch($path, $siteHandle)
            ?? $this->findWildcardMatch($path, $siteHandle);
    }

    private function findExactMatch(string $path, string $siteHandle): ?Redirect
    {
        return RedirectFacade::query()
            ->where('source', $path)
            ->where('site', $siteHandle)
            ->where('enabled', true)
            ->first();
    }

    private function findWildcardMatch(string $path, string $siteHandle): ?Redirect
    {
        return RedirectFacade::query()
            ->where('site', $siteHandle)
            ->where('enabled', true)
            ->get()
            ->filter->usesWildcard()
            ->first(fn (Redirect $redirect) => WildcardUrlMatcher::match($redirect->source(), $path) !== null);
    }

    private function recordError(string $path, string $siteHandle): void
    {
        if (config('statamic.seo-pro.redirects.errors.enabled')) {
            RecordError::dispatch($path, $siteHandle);
        }
    }

    private function resolveDestination(Redirect $redirect, string $path, Request $request): string
    {
        $destination = $redirect->destination();

        if ($redirect->usesWildcard()) {
            $captures = WildcardUrlMatcher::match($redirect->source(), $path);
            $destination = WildcardUrlMatcher::resolveDestination($destination, $captures);
        }

        if (Str::startsWith($destination, 'entry::') && $data = Data::find($destination)) {
            $destination = $data->absoluteUrl();
        }

        if ($request->getQueryString() && config('statamic.seo-pro.redirects.preserve_query_string')) {
            $separator = str_contains($destination, '?') ? '&' : '?';
            $destination .= $separator.$request->getQueryString();
        }

        return $destination;
    }
}
