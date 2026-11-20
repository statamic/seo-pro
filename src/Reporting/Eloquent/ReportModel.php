<?php

namespace Statamic\SeoPro\Reporting\Eloquent;

use Illuminate\Database\Eloquent\Model;

class ReportModel extends Model
{
    protected $table = 'seo_pro_reports';

    protected $guarded = [];

    public function casts(): array
    {
        return [
            'date' => 'datetime',
            'score' => 'float',
            'pages_crawled' => 'integer',
            'pages_actionable' => 'integer',
            'results' => 'json',
        ];
    }
}
