<?php

namespace Statamic\SeoPro\BrokenLinks\Eloquent;

use Illuminate\Database\Eloquent\Model;

class ExternalLinkModel extends Model
{
    protected $table = 'seo_pro_external_links';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(fn (ExternalLinkModel $model): string => $model->url_hash = hash('sha256', $model->url));
    }

    public function casts(): array
    {
        return [
            'status_code' => 'integer',
            'broken_since' => 'datetime',
            'checked_at' => 'datetime',
            'next_check_at' => 'datetime',
            'notified_at' => 'datetime',
            'references' => 'json',
        ];
    }
}
