<?php

namespace Statamic\SeoPro\Llms;

use Illuminate\Support\Facades\Route;
use Statamic\Facades\Site;
use Statamic\SeoPro\Http\Controllers\LlmsController;

class LlmsRoutes
{
    public static function register(?iterable $sites = null): void
    {
        $sites = collect($sites ?? Site::all());
        $defaultSite = $sites->first();
        $registered = [];
        $routeName = 'statamic.seo-pro.llms.show';

        if (! $defaultSite) {
            return;
        }

        $sites
            ->sortByDesc(fn ($site) => $site->handle() === $defaultSite->handle())
            ->each(function ($site) use ($defaultSite, &$registered, $routeName) {
                $host = parse_url($site->absoluteUrl(), PHP_URL_HOST);
                $path = Llms::relativePath($site);
                $key = strtolower(($host ?: '').'/'.$path);

                if (isset($registered[$key])) {
                    return;
                }

                $registered[$key] = true;

                $route = $host
                    ? Route::domain($host)->get($path, [LlmsController::class, 'show'])
                    : Route::get($path, [LlmsController::class, 'show']);

                $route
                    ->middleware('statamic.web')
                    ->defaults('seoProSite', $site->handle());

                $route->name(
                    $site->handle() === $defaultSite->handle()
                        ? $routeName
                        : $routeName.'.'.$site->handle()
                );
            });
    }
}
