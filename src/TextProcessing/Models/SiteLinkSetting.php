<?php

namespace Statamic\SeoPro\TextProcessing\Models;

use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $site
 * @property array $ignored_phrases
 * @property float $keyword_threshold
 * @property int $min_internal_links
 * @property int $max_internal_links
 * @property int $min_external_links
 * @property int $max_external_links
 */
class SiteLinkSetting extends Model
{
    use HasTimestamps;

    protected $table = 'seopro_site_link_settings';

    protected $fillable = [
        'site',
    ];

    protected $casts = [
        'ignored_phrases' => 'array',
    ];
}