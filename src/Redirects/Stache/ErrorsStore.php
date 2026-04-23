<?php

namespace Statamic\SeoPro\Redirects\Stache;

use Statamic\Entries\GetSlugFromPath;
use Statamic\Facades\YAML;
use Statamic\SeoPro\Facades;
use Statamic\SeoPro\Redirects\Error;
use Statamic\Stache\Stores\BasicStore;
use Statamic\Support\Arr;

class ErrorsStore extends BasicStore
{
    protected $storeIndexes = [
        'id', 'url', 'hits',
    ];

    public function key()
    {
        return 'errors';
    }

    public function makeItemFromFile($path, $contents): Error
    {
        $data = YAML::file($path)->parse($contents);

        return Facades\Error::make()
            ->id((new GetSlugFromPath)($path))
            ->url(Arr::pull($data, 'url'))
            ->hits(Arr::pull($data, 'hits', 0))
            ->lastHitAt(Arr::pull($data, 'last_hit_at'))
            ->data($data);
    }
}
