<?php

Route::get(config('statamic.seo-pro.sitemap.url'), 'SitemapController@show');
Route::get(config('statamic.seo-pro.humans.url'), 'HumansController@show');
