<?php

namespace Statamic\SeoPro\Facades;

use Illuminate\Support\Facades\Facade;
use Statamic\Fields\Blueprint;
use Statamic\SeoPro\Redirects\RedirectQueryBuilder;
use Statamic\SeoPro\Redirects\RedirectRepository;

/**
 * @method static \Illuminate\Support\Collection all()
 * @method static RedirectQueryBuilder query()
 * @method static null|\Statamic\SeoPro\Redirects\Redirect find($id)
 * @method static \Statamic\SeoPro\Redirects\Redirect make()
 * @method static void save(Redirect $redirect)
 * @method static void delete(Redirect $redirect)
 * @method static Blueprint blueprint()
 *
 * @see \Statamic\SeoPro\Redirects\Stache\RedirectRepository
 * @link \Statamic\SeoPro\Redirects\Stache\RedirectQueryBuilder
 * @see \Statamic\SeoPro\Redirects\Eloquent\RedirectRepository
 * @link \Statamic\SeoPro\Redirects\Eloquent\RedirectQueryBuilder
 * @link \Statamic\SeoPro\Redirects\Redirect
 */
class Redirect extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RedirectRepository::class;
    }
}
