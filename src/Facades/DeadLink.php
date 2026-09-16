<?php

namespace Statamic\SeoPro\Facades;

use Illuminate\Support\Facades\Facade;
use Statamic\Fields\Blueprint;
use Statamic\SeoPro\DeadLinks\Link;
use Statamic\SeoPro\DeadLinks\LinkQueryBuilder;
use Statamic\SeoPro\DeadLinks\LinkRepository;

/**
 * @method static \Illuminate\Support\Collection all()
 * @method static LinkQueryBuilder query()
 * @method static null|Link find($id)
 * @method static Link make()
 * @method static void save(Link $link)
 * @method static void delete(Link $link)
 * @method static Blueprint blueprint()
 *
 * @see \Statamic\SeoPro\DeadLinks\Stache\LinkRepository
 * @link \Statamic\SeoPro\DeadLinks\Stache\LinkQueryBuilder
 * @see \Statamic\SeoPro\DeadLinks\Eloquent\LinkRepository
 * @link \Statamic\SeoPro\DeadLinks\Eloquent\LinkQueryBuilder
 * @link \Statamic\SeoPro\DeadLinks\Link
 */
class DeadLink extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LinkRepository::class;
    }
}
