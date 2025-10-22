<?php

use Statamic\SeoPro\Http\Controllers;

Route::get('seo-pro', Controllers\CP\IndexController::class)->name('seo-pro.index');
Route::get('seo-pro/reports', [Controllers\CP\ReportController::class, 'index'])->name('seo-pro.reports.index');
Route::get('seo-pro/reports/create', [Controllers\CP\ReportController::class, 'create'])->name('seo-pro.reports.create');
Route::get('seo-pro/reports/{seo_pro_report}', [Controllers\CP\ReportController::class, 'show'])->name('seo-pro.reports.show');
Route::get('seo-pro/reports/{seo_pro_report}/pages', [Controllers\CP\ReportPagesController::class, 'index'])->name('seo-pro.reports.pages.index');
Route::delete('seo-pro/reports/{seo_pro_report}', [Controllers\CP\ReportController::class, 'destroy'])->name('seo-pro.reports.destroy');

Route::get('seo-pro/site-defaults/edit', [Controllers\CP\SiteDefaultsController::class, 'edit'])->name('seo-pro.site-defaults.edit');
Route::patch('seo-pro/site-defaults', [Controllers\CP\SiteDefaultsController::class, 'update'])->name('seo-pro.site-defaults.update');

Route::get('seo-pro/site-defaults/configure', [Controllers\CP\ConfigureSiteDefaultsController::class, 'edit'])->name('seo-pro.site-defaults.configure.edit');
Route::patch('seo-pro/site-defaults/configure', [Controllers\CP\ConfigureSiteDefaultsController::class, 'update'])->name('seo-pro.site-defaults.configure.update');

Route::get('seo-pro/section-defaults', [Controllers\CP\SectionDefaultsController::class, 'index'])->name('seo-pro.section-defaults.index');
Route::get('seo-pro/section-defaults/collections/{seo_pro_collection}/edit', [Controllers\CP\CollectionDefaultsController::class, 'edit'])->name('seo-pro.section-defaults.collections.edit');
Route::patch('seo-pro/section-defaults/collections/{seo_pro_collection}', [Controllers\CP\CollectionDefaultsController::class, 'update'])->name('seo-pro.section-defaults.collections.update');
Route::get('seo-pro/section-defaults/taxonomies/{seo_pro_taxonomy}/edit', [Controllers\CP\TaxonomyDefaultsController::class, 'edit'])->name('seo-pro.section-defaults.taxonomies.edit');
Route::patch('seo-pro/section-defaults/taxonomies/{seo_pro_taxonomy}', [Controllers\CP\TaxonomyDefaultsController::class, 'update'])->name('seo-pro.section-defaults.taxonomies.update');
