<?php

namespace Statamic\SeoPro\Facades;

use Illuminate\Support\Facades\Facade;
use Statamic\Fields\Blueprint;
use Statamic\SeoPro\BrokenLinks\ExternalLinkQueryBuilder;
use Statamic\SeoPro\BrokenLinks\ExternalLinkRepository;

/**
 * @method static \Illuminate\Support\Collection all()
 * @method static ExternalLinkQueryBuilder query()
 * @method static null|\Statamic\SeoPro\BrokenLinks\ExternalLink find($id)
 * @method static null|\Statamic\SeoPro\BrokenLinks\ExternalLink findByUrl(string $url, string $site)
 * @method static \Statamic\SeoPro\BrokenLinks\ExternalLink make()
 * @method static void save(\Statamic\SeoPro\BrokenLinks\ExternalLink $link)
 * @method static void delete(\Statamic\SeoPro\BrokenLinks\ExternalLink $link)
 * @method static Blueprint blueprint()
 *
 * @see \Statamic\SeoPro\BrokenLinks\Stache\ExternalLinkRepository
 * @link \Statamic\SeoPro\BrokenLinks\Stache\ExternalLinkQueryBuilder
 * @see \Statamic\SeoPro\BrokenLinks\Eloquent\ExternalLinkRepository
 * @link \Statamic\SeoPro\BrokenLinks\Eloquent\ExternalLinkQueryBuilder
 * @link \Statamic\SeoPro\BrokenLinks\ExternalLink
 */
class ExternalLink extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ExternalLinkRepository::class;
    }
}
