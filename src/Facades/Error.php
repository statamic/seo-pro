<?php

namespace Statamic\SeoPro\Facades;

use Illuminate\Support\Facades\Facade;
use Statamic\Fields\Blueprint;
use Statamic\SeoPro\Redirects\ErrorQueryBuilder;
use Statamic\SeoPro\Redirects\ErrorRepository;

/**
 * @method static \Illuminate\Support\Collection all()
 * @method static ErrorQueryBuilder query()
 * @method static null|\Statamic\SeoPro\Redirects\Error find($id)
 * @method static \Statamic\SeoPro\Redirects\Error make()
 * @method static void save(Error $error)
 * @method static void delete(Error $error)
 * @method static Blueprint blueprint()
 *
 * @see \Statamic\SeoPro\Redirects\Stache\ErrorRepository
 * @link \Statamic\SeoPro\Redirects\Stache\ErrorQueryBuilder
 * @see \Statamic\SeoPro\Redirects\Eloquent\ErrorRepository
 * @link \Statamic\SeoPro\Redirects\Eloquent\ErrorQueryBuilder
 * @link \Statamic\SeoPro\Redirects\Error
 */
class Error extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ErrorRepository::class;
    }
}
