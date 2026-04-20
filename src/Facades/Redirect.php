<?php

namespace Statamic\SeoPro\Facades;

use Illuminate\Support\Facades\Facade;
use Statamic\SeoPro\Redirects\RedirectRepository;

class Redirect extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RedirectRepository::class;
    }
}
