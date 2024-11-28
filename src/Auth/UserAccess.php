<?php

namespace Statamic\SeoPro\Auth;

use Illuminate\Support\Collection as IlluminateCollection;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Facades\User;

class UserAccess
{
    public static function getCollectionsForCurrentUser(): IlluminateCollection
    {
        return Blink::once('seo_pro_user_visible_collections', function () {
            return Collection::all()
                ->filter(function ($collection) {
                    return User::current()->can('view', $collection);
                })
                ->pluck('handle');
        });
    }

    public static function getSitesForCurrentUser(): IlluminateCollection
    {
        return Blink::once('seo_pro_user_visible_sites', function () {
            return Site::all()
                ->filter(function ($site) {
                    return User::current()->can('view', $site);
                })
                ->map(fn ($site) => $site->handle())
                ->values();
        });
    }
}
