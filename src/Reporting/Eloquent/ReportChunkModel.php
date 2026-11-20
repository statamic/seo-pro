<?php

namespace Statamic\SeoPro\Reporting\Eloquent;

use Illuminate\Database\Eloquent\Model;

class ReportChunkModel extends Model
{
    protected $table = 'seo_pro_report_chunks';

    protected $guarded = [];

    public function casts(): array
    {
        return [
            'content_ids' => 'json',
        ];
    }
}
