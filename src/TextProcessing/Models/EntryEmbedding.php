<?php

namespace Statamic\SeoPro\TextProcessing\Models;

use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $entry_id
 * @property string $site
 * @property string $collection
 * @property string $blueprint
 * @property array $embedding
 * @property string $content_hash
 * @property string $configuration_hash
 */
class EntryEmbedding extends Model
{
    use HasTimestamps;

    protected $table = 'seopro_entry_embeddings';

    protected $fillable = [
        'entry_id',
        'site',
        'collection',
        'blueprint',
        'embedding',
        'content_hash',
    ];

    protected $casts = [
        'embedding' => 'array',
    ];

    public function entryLink(): HasOne
    {
        return $this->hasOne(EntryLink::class, 'entry_id', 'entry_id');
    }
}
