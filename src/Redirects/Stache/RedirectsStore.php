<?php

namespace Statamic\SeoPro\Redirects\Stache;

use Statamic\Entries\GetSlugFromPath;
use Statamic\Facades\YAML;
use Statamic\SeoPro\Facades;
use Statamic\SeoPro\Redirects\Redirect;
use Statamic\Stache\Stores\BasicStore;
use Statamic\Support\Arr;

class RedirectsStore extends BasicStore
{
    protected $storeIndexes = [
        'id', 'source', 'enabled', 'hits',
    ];

    public function key()
    {
        return 'redirects';
    }

    public function makeItemFromFile($path, $contents): Redirect
    {
        $data = YAML::file($path)->parse($contents);

        return Facades\Redirect::make()
            ->id((new GetSlugFromPath)($path))
            ->source(Arr::pull($data, 'source'))
            ->destination(Arr::pull($data, 'destination'))
            ->responseCode(Arr::pull($data, 'response_code', 301))
            ->enabled(Arr::pull($data, 'enabled', true))
            ->hits(Arr::pull($data, 'hits', 0))
            ->lastHitAt(Arr::pull($data, 'last_hit_at'))
            ->data($data);
    }
}
