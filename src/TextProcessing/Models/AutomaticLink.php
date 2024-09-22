<?php

namespace Statamic\SeoPro\TextProcessing\Models;

use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $site
 * @property bool $is_active
 * @property string $link_text
 * @property string $link_target
 * @property ?string $entry_id
 */
class AutomaticLink extends Model
{
    use HasTimestamps;

    protected $table = 'seopro_global_automatic_links';

    protected $fillable = [
        'site',
        'is_active',
        'link_text',
        'link_target',
    ];
}
