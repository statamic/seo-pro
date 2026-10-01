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
            ->url(Arr::pull($data, 'url'))
            ->status(Arr::pull($data, 'status', ExternalLink::STATUS_PENDING))
            ->statusCode(Arr::pull($data, 'status_code'))
            ->error(Arr::pull($data, 'error'))
            ->failingSince(Arr::pull($data, 'failing_since'))
            ->checkedAt(Arr::pull($data, 'checked_at'))
            ->nextCheckAt(Arr::pull($data, 'next_check_at'))
            ->notifiedAt(Arr::pull($data, 'notified_at'))
            ->references(Arr::pull($data, 'references', []))
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

    public function getItemFilter(SplFileInfo $file): bool
    {
        return $file->getExtension() === 'yaml';
    }
}
