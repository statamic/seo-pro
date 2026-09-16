<?php

namespace Statamic\SeoPro\DeadLinks\Eloquent;

use Illuminate\Database\Eloquent\Model;

class LinkModel extends Model
{
    protected $table = 'seo_pro_dead_links';

    protected $guarded = [];

    public function casts(): array
    {
        return [
            'status_code' => 'integer',
            'consecutive_failures' => 'integer',
            'checked_at' => 'datetime',
            'next_check_at' => 'datetime',
            'notified_at' => 'datetime',
            'references' => 'json',
            'data' => 'json',
        ];
    }
}
