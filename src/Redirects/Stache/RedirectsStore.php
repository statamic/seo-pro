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
        'id', 'source_url', 'enabled',
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
            ->sourceUrl(Arr::pull($data, 'source_url'))
            ->destinationUrl(Arr::pull($data, 'destination_url'))
            ->statusCode(Arr::pull($data, 'status_code'))
            ->enabled(Arr::pull($data, 'enabled'))
            ->data($data);
    }
}
