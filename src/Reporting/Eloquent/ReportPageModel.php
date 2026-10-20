<?php

namespace Statamic\SeoPro\Reporting\Eloquent;

use Illuminate\Database\Eloquent\Model;

class ReportPageModel extends Model
{
    protected $table = 'seo_pro_report_pages';

    protected $guarded = [];

    public function casts(): array
    {
        return [
            'data' => 'json',
            'results' => 'json',
        ];
    }
}
