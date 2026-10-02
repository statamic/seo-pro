<?php

namespace Statamic\SeoPro\BrokenLinks\Stache;

use SplFileInfo;
use Statamic\Entries\GetSlugFromPath;
use Statamic\Facades\Site;
use Statamic\Facades\YAML;
use Statamic\SeoPro\BrokenLinks\ExternalLink;
use Statamic\SeoPro\Facades;
use Statamic\Stache\Stores\BasicStore;
use Statamic\Support\Arr;
use Statamic\Support\Str;

class ExternalLinksStore extends BasicStore
{
    protected $storeIndexes = [
        'id', 'site', 'url', 'status',
    ];

    public function key(): string
    {
        return 'seo_pro_external_links';
    }

    public function getItemKey($item): string
    {
        if (Site::multiEnabled()) {
            return $item->site().'::'.$item->id();
        }

        return $item->id();
    }

    public function makeItemFromFile($path, $contents): ExternalLink
    {
        $data = YAML::file($path)->parse($contents);

        $site = $this->extractSiteFromPath($path);

        return Facades\ExternalLink::make()
            ->id((new GetSlugFromPath)($path))
            ->site($site)
            ->url(Arr::get($data, 'url'))
            ->status(Arr::get($data, 'status', ExternalLink::STATUS_PENDING))
            ->statusCode(Arr::get($data, 'status_code'))
            ->error(Arr::get($data, 'error'))
            ->failingSince(Arr::get($data, 'failing_since'))
            ->checkedAt(Arr::get($data, 'checked_at'))
            ->nextCheckAt(Arr::get($data, 'next_check_at'))
            ->notifiedAt(Arr::get($data, 'notified_at'))
            ->references(Arr::get($data, 'references', []));
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

    public function getItemFilter(SplFileInfo $file): bool
    {
        return $file->getExtension() === 'yaml';
    }
}
