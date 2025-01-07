<?php

namespace Statamic\SeoPro\Models;

use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $entry_id
 * @property string $cached_title
 * @property string $cached_uri
 * @property string $analyzed_content
 * @property array $content_mapping
 * @property array $external_links
 * @property array $internal_links
 * @property array $normalized_internal_links
 * @property array $normalized_external_links
 * @property int $external_link_count
 * @property int $internal_link_count
 * @property int $inbound_internal_link_count
 * @property string $site
 * @property string $collection
 * @property string $content_hash
 * @property array $ignored_entries
 * @property array $ignored_phrases
 * @property bool $can_be_suggested
 * @property bool $include_in_reporting
 */
class EntryLink extends Model
{
    use HasTimestamps;

    protected $table = 'seopro_entry_links';

    protected $fillable = [
        'entry_id',
        'analyzed_content',
        'content_mapping',
        'external_link_count',
        'internal_link_count',
        'external_links',
        'internal_links',
        'site',
        'collection',
        'cached_title',
        'cached_uri',
    ];

    protected $casts = [
        'external_links' => 'array',
        'internal_links' => 'array',
        'normalized_external_links' => 'array',
        'normalized_internal_links' => 'array',
        'content_mapping' => 'array',
        'ignored_entries' => 'array',
        'ignored_phrases' => 'array',
    ];

    public function collectionSettings(): HasOne
    {
        return $this->hasOne(CollectionLinkSetting::class, 'collection', 'collection');
    }
}
