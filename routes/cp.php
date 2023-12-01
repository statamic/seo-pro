<?php

use Statamic\SeoPro\Http\Controllers;

Route::view('seo-pro', 'seo-pro::index')->name('seo-pro.index');
Route::get('seo-pro/reports', [Controllers\ReportController::class, 'index'])->name('seo-pro.reports.index');
Route::get('seo-pro/reports/create', [Controllers\ReportController::class, 'create'])->name('seo-pro.reports.create');
Route::get('seo-pro/reports/{seo_pro_report}', [Controllers\ReportController::class, 'show'])->name('seo-pro.reports.show');
Route::delete('seo-pro/reports/{seo_pro_report}', [Controllers\ReportController::class, 'destroy'])->name('seo-pro.reports.destroy');

Route::get('seo-pro/site-defaults/edit', [Controllers\SiteDefaultsController::class, 'edit'])->name('seo-pro.site-defaults.edit');
Route::post('seo-pro/site-defaults', [Controllers\SiteDefaultsController::class, 'update'])->name('seo-pro.site-defaults.update');

Route::view('seo-pro/section-defaults', 'seo-pro::sections')->name('seo-pro.section-defaults.index');
Route::get('seo-pro/section-defaults/collections/{seo_pro_collection}/edit', [Controllers\CollectionDefaultsController::class, 'edit'])->name('seo-pro.section-defaults.collections.edit');
Route::post('seo-pro/section-defaults/collections/{seo_pro_collection}', [Controllers\CollectionDefaultsController::class, 'update'])->name('seo-pro.section-defaults.collections.update');
Route::get('seo-pro/section-defaults/taxonomies/{seo_pro_taxonomy}/edit', [Controllers\TaxonomyDefaultsController::class, 'edit'])->name('seo-pro.section-defaults.taxonomies.edit');
Route::post('seo-pro/section-defaults/taxonomies/{seo_pro_taxonomy}', [Controllers\TaxonomyDefaultsController::class, 'update'])->name('seo-pro.section-defaults.taxonomies.update');
