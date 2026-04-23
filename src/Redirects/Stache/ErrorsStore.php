<?php

namespace Statamic\SeoPro\Redirects\Stache;

use Statamic\Entries\GetSlugFromPath;
use Statamic\Facades\Site;
use Statamic\Facades\YAML;
use Statamic\SeoPro\Facades;
use Statamic\SeoPro\Redirects\Error;
use Statamic\Stache\Stores\BasicStore;
use Statamic\Support\Arr;
use Statamic\Support\Str;
use SplFileInfo;

class ErrorsStore extends BasicStore
{
    protected $storeIndexes = [
        'id', 'site', 'url', 'hits',
    ];

    public function key()
    {
        return 'errors';
    }

    public function makeItemFromFile($path, $contents): Error
    {
        $data = YAML::file($path)->parse($contents);

        $site = $this->extractSiteFromPath($path);

        return Facades\Error::make()
            ->id((new GetSlugFromPath)($path))
            ->site($site)
            ->url(Arr::pull($data, 'url'))
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
