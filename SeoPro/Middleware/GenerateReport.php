<?php

namespace Statamic\Addons\SeoPro\Middleware;

use Closure;
use Illuminate\Support\Facades\Artisan;
use Statamic\Addons\SeoPro\Reporting\Report;

class GenerateReport
{
    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response)
    {
        if (empty($reports = Report::$reportsToGenerate)) {
            return;
        }

        foreach ($reports as $id) {
            Artisan::queue('seopro:report:generate', ['--report' => $id]);
        }
    }
}
