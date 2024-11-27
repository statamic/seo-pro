<?php

namespace Statamic\SeoPro\Models;

use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $collection
 * @property bool $linking_enabled
 * @property bool $allow_linking_across_sites
 * @property bool $allow_linking_to_all_collections
 * @property array $linkable_collections
 */
class CollectionLinkSettings extends Model
{
    use HasTimestamps;

    protected $table = 'seopro_collection_link_settings';

    protected $fillable = [
        'collection',
    ];

    protected $casts = [
        'linkable_collections' => 'array',
    ];
}
