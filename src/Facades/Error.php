<?php

namespace Statamic\SeoPro\Facades;

use Illuminate\Support\Facades\Facade;
use Statamic\SeoPro\Redirects\ErrorRepository;

class Error extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ErrorRepository::class;
    }
}
