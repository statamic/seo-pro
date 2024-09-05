<?php

use Statamic\SeoPro\Http\Controllers;

Route::get(config('statamic.seo-pro.sitemap.url'), [Controllers\SitemapController::class, 'index']);
Route::get(config('statamic.seo-pro.sitemap.pagination.url'), [Controllers\SitemapController::class, 'show'])->name('statamic.seo-pro.sitemap.page.show');
Route::get(config('statamic.seo-pro.humans.url'), [Controllers\HumansController::class, 'show']);
