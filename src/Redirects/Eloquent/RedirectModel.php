<?php

namespace Statamic\SeoPro\Redirects\Eloquent;

use Illuminate\Database\Eloquent\Model;

class RedirectModel extends Model
{
    protected $table = 'redirects';

    protected $guarded = [];

    public function casts(): array
    {
        return [
            'response_code' => 'integer',
            'enabled' => 'boolean',
            'hits' => 'integer',
            'last_hit_at' => 'datetime',
            'data' => 'json',
        ];
    }
}