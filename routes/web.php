<?php

use Statamic\SeoPro\Http\Controllers;

Route::get('sitemap.xsl', Controllers\SitemapXslController::class);
Route::get(config('statamic.seo-pro.sitemap.url'), [Controllers\SitemapController::class, 'index']);
Route::get(config('statamic.seo-pro.sitemap.pagination.url', 'sitemap_{page}.xml'), [Controllers\SitemapController::class, 'show'])->name('statamic.seo-pro.sitemap.page.show');
Route::get(config('statamic.seo-pro.humans.url'), [Controllers\HumansController::class, 'show']);
Route::get('llms.txt', [Controllers\LlmsController::class, 'show'])
    ->middleware('statamic.web')
    ->name('statamic.seo-pro.llms.show');

Route::get('{sitePath}/llms.txt', [Controllers\LlmsController::class, 'show'])
    ->where('sitePath', '.+')
    ->middleware('statamic.web');
