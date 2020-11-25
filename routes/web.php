<?php

use Statamic\SeoPro\Http\Controllers;

Route::get(config('statamic.seo-pro.sitemap.url'), [Controllers\SitemapController::class, 'show']);
Route::get(config('statamic.seo-pro.humans.url'), [Controllers\HumansController::class, 'show']);
