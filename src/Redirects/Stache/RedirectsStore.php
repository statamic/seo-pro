<?php

namespace Statamic\SeoPro\Redirects\Stache;

use SplFileInfo;
use Statamic\Entries\GetSlugFromPath;
use Statamic\Facades\Site;
use Statamic\Facades\YAML;
use Statamic\SeoPro\Facades;
use Statamic\SeoPro\Redirects\Redirect;
use Statamic\Stache\Stores\BasicStore;
use Statamic\Support\Arr;
use Statamic\Support\Str;

class RedirectsStore extends BasicStore
{
    protected $storeIndexes = [
        'id', 'site', 'source', 'enabled', 'hits',
    ];

    public function key()
    {
        return 'seo_pro_redirects';
    }

    public function makeItemFromFile($path, $contents): Redirect
    {
        $data = YAML::file($path)->parse($contents);

        $site = $this->extractSiteFromPath($path);

        return Facades\Redirect::make()
            ->id((new GetSlugFromPath)($path))
            ->site($site)
            ->source(Arr::pull($data, 'source'))
            ->destination(Arr::pull($data, 'destination'))
            ->responseCode(Arr::pull($data, 'response_code', 301))
            ->enabled(Arr::pull($data, 'enabled', true))
            ->hits(Arr::pull($data, 'hits', 0))
            ->lastHitAt(Arr::pull($data, 'last_hit_at'))
            ->data($data);
    }

    protected function extractSiteFromPath(string $path): string
    {
        $site = Site::default()->handle();
        $relative = Str::after($path, $this->directory());

        if (Site::multiEnabled() && str_contains($relative, '/')) {
            $site = Str::before($relative, '/');
        }

        return $site;
    }

    public function getItemFilter(SplFileInfo $file)
    {
        return $file->getExtension() === 'yaml';
    }
}
