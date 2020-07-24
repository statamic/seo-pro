<?php

Route::get('seo-pro/reports', 'ReportController@index')->name('seo-pro.reports.index');
Route::post('seo-pro/reports', 'ReportController@store')->name('seo-pro.reports.store');
Route::get('seo-pro/reports/{seo_pro_report}', 'ReportController@show')->name('seo-pro.reports.show');

Route::get('seo-pro/site-defaults/edit', 'SiteDefaultsController@edit')->name('seo-pro.site-defaults.edit');
Route::post('seo-pro/site-defaults', 'SiteDefaultsController@update')->name('seo-pro.site-defaults.update');

Route::view('seo-pro/section-defaults', 'seo-pro::sections')->name('seo-pro.section-defaults.index');
Route::get('seo-pro/section-defaults/collections/{seo_pro_collection}/edit', 'CollectionDefaultsController@edit')->name('seo-pro.section-defaults.collections.edit');
Route::post('seo-pro/section-defaults/collections/{seo_pro_collection}', 'CollectionDefaultsController@update')->name('seo-pro.section-defaults.collections.update');
Route::get('seo-pro/section-defaults/taxonomies/{seo_pro_taxonomy}/edit', 'TaxonomyDefaultsController@edit')->name('seo-pro.section-defaults.taxonomies.edit');
Route::post('seo-pro/section-defaults/taxonomies/{seo_pro_taxonomy}', 'TaxonomyDefaultsController@update')->name('seo-pro.section-defaults.taxonomies.update');
