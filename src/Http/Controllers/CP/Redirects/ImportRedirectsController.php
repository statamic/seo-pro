<?php

namespace Statamic\SeoPro\Http\Controllers\CP\Redirects;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelReader;
use Statamic\Facades\Site;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\SeoPro\Facades;
use Statamic\SeoPro\Redirects\Redirect;

class ImportRedirectsController extends CpController
{
    public function __invoke(Request $request): JsonResponse
    {
        $this->authorize('create', Redirect::class);

        $request->validate([
            'file' => ['required', 'array', 'min:1'],
        ]);

        $disk = config('statamic.system.file_uploads.disk', 'local');
        $uploadPath = config('statamic.system.file_uploads.path', 'statamic/file-uploads');

        $path = Storage::disk($disk)->path("{$uploadPath}/{$request->file[0]}");

        $reader = SimpleExcelReader::create($path);
        $headers = collect($reader->getHeaders())->map(fn (string $header): string => (string) Str::of($header)->lower()->snake());
        $rows = $reader->useHeaders($headers->all())->getRows();

        if (! $headers->contains('source') || ! $headers->contains('destination')) {
            return response()->json([
                'message' => 'The CSV must have at least two columns: source, destination.',
            ], 422);
        }

        $created = 0;
        $updated = 0;
        $site = Site::selected()->handle();
        $defaultResponseCode = config('statamic.seo-pro.redirects.default_response_code', 301);

        $rows->each(function (array $row) use ($headers, $site, $defaultResponseCode, &$created, &$updated) {
            $source = trim($row['source'] ?? '');
            $destination = trim($row['destination'] ?? '');

            if (empty($source)) {
                return;
            }

            $existingRedirect = Facades\Redirect::query()
                ->where('source', $source)
                ->where('site', $site)
                ->first();

            if ($existingRedirect) {
                $existingRedirect->destination($destination);

                if ($headers->contains('response_code')) {
                    $existingRedirect->responseCode((int) $row['response_code'] ?: $defaultResponseCode);
                }

                if ($headers->contains('enabled')) {
                    $existingRedirect->enabled(filter_var($row['enabled'], FILTER_VALIDATE_BOOLEAN));
                }

                if ($headers->contains('description') && $description = trim($row['description'])) {
                    $existingRedirect->set('description', $description);
                }

                $existingRedirect->save();
                $updated++;
            } else {
                $enabled = $headers->contains('enabled') ? filter_var($row['enabled'], FILTER_VALIDATE_BOOLEAN) : true;
                $responseCode = $headers->contains('response_code') ? ((int) $row['response_code'] ?: $defaultResponseCode) : $defaultResponseCode;

                $redirect = Facades\Redirect::make()
                    ->site($site)
                    ->source($source)
                    ->destination($destination)
                    ->responseCode($responseCode)
                    ->enabled($enabled);

                if ($headers->contains('description') && $description = trim($row['description'])) {
                    $redirect->set('description', $description);
                }

                $redirect->save();
                $created++;
            }
        });

        Storage::disk($disk)->delete("{$uploadPath}/{$request->file[0]}");

        return response()->json([
            'created' => $created,
            'updated' => $updated,
        ]);
    }
}
