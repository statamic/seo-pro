<?php

use Statamic\SeoPro\Http\Controllers;
use Statamic\SeoPro\Llms\LlmsRoutes;

Route::get('sitemap.xsl', Controllers\SitemapXslController::class);
Route::get(config('statamic.seo-pro.sitemap.url'), [Controllers\SitemapController::class, 'index']);
Route::get(config('statamic.seo-pro.sitemap.pagination.url', 'sitemap_{page}.xml'), [Controllers\SitemapController::class, 'show'])->name('statamic.seo-pro.sitemap.page.show');
Route::get(config('statamic.seo-pro.humans.url'), [Controllers\HumansController::class, 'show']);

LlmsRoutes::register();
