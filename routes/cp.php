<?php

Route::get('seo-pro/reports', 'ReportController@index')->name('seo-pro.reports.index');
Route::post('seo-pro/reports', 'ReportController@store')->name('seo-pro.reports.store');
Route::get('seo-pro/reports/{report}', 'ReportController@show')->name('seo-pro.reports.show');
