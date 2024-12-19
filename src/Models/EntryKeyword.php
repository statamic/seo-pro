<?php

namespace Statamic\SeoPro\Models;

use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $entry_id
 * @property string $site
 * @property string $collection
 * @property string $blueprint
 * @property array $meta_keywords
 * @property array $content_keywords
 * @property string $content_hash
 */
class EntryKeyword extends Model
{
    use HasTimestamps;

    protected $table = 'seopro_entry_keywords';

    protected $fillable = [
        'entry_id',
        'site',
        'collection',
        'meta_keywords',
        'content_keywords',
        'content_hash',
    ];

    protected $casts = [
        'meta_keywords' => 'array',
        'content_keywords' => 'array',
    ];
}
