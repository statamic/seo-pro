<?php

namespace Statamic\SeoPro\Redirects\Eloquent;

use Illuminate\Database\Eloquent\Model;

class ErrorModel extends Model
{
    protected $table = 'seo_pro_errors';

    protected $guarded = [];

    public function casts(): array
    {
        return [
            'hits' => 'integer',
            'last_hit_at' => 'datetime',
            'data' => 'json',
        ];
    }
}
