<?php

namespace Statamic\SeoPro\Http\Controllers\CP\Redirects;

use Spatie\SimpleExcel\SimpleExcelWriter;
use Statamic\Facades\Site;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\Facades;
use Statamic\SeoPro\Redirects\Redirect;

class ExportRedirectsController extends CpController
{
    public function __invoke()
    {
        $this->authorize('index', Redirect::class);

        $query = Facades\Redirect::query();

        if (Site::multiEnabled()) {
            $query->whereIn('site', Site::authorized()->map->handle()->all());
        }

        $path = tempnam(sys_get_temp_dir(), 'redirects-export-').'.csv';

        $writer = SimpleExcelWriter::createWithoutBom($path);

        $query->get()->each(function ($redirect) use ($writer) {
            $writer->addRow([
                'source' => $redirect->source(),
                'destination' => $redirect->destination(),
                'response_code' => $redirect->responseCode(),
                'enabled' => $redirect->enabled() ? 'true' : 'false',
                'description' => $redirect->get('description'),
            ]);
        });

        $writer->close();

        return response()->download($path, 'redirects.csv', [
            'Content-Type' => 'text/csv',
        ])->deleteFileAfterSend();
    }
}
