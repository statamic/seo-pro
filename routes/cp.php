<?php

Route::get('seo-pro/reports', 'ReportController@index')->name('seo-pro.reports.index');
Route::post('seo-pro/reports', 'ReportController@store')->name('seo-pro.reports.store');
Route::get('seo-pro/reports/{report}', 'ReportController@show')->name('seo-pro.reports.show');

Route::get('seo-pro/site-defaults/edit', 'SiteDefaultsController@edit')->name('seo-pro.site-defaults.edit');

Route::get('seo-pro/section-defaults', 'SectionDefaultsController@index')->name('seo-pro.section-defaults.index');
