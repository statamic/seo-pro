<?php

namespace Statamic\SeoPro\DeadLinks\Eloquent;

use Illuminate\Database\Eloquent\Model;

class LinkModel extends Model
{
    protected $table = 'seo_pro_dead_links';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(fn (LinkModel $model) => $model->url_hash = hash('sha256', $model->url));
    }

    public function casts(): array
    {
        return [
            'status_code' => 'integer',
            'failing_since' => 'datetime',
            'checked_at' => 'datetime',
            'next_check_at' => 'datetime',
            'notified_at' => 'datetime',
            'references' => 'json',
            'subjects' => 'json',
            'data' => 'json',
        ];
    }
}
