<?php

namespace Statamic\SeoPro\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;

class SeoDefaults extends Model
{
    protected $casts = [
        'data' => AsArrayObject::class,
    ];
}
