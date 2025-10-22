<?php

use Illuminate\Support\Facades\Route;
use Statamic\SeoPro\Http\Controllers;

Route::prefix('seo-pro')->name('seo-pro.')->group(function () {
    Route::get('/', Controllers\CP\IndexController::class)->name('index');
    Route::get('reports', [Controllers\CP\ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/create', [Controllers\CP\ReportController::class, 'create'])->name('reports.create');
    Route::get('reports/{seo_pro_report}', [Controllers\CP\ReportController::class, 'show'])->name('reports.show');
    Route::get('reports/{seo_pro_report}/pages', [Controllers\CP\ReportPagesController::class, 'index'])->name('reports.pages.index');
    Route::delete('reports/{seo_pro_report}', [Controllers\CP\ReportController::class, 'destroy'])->name('reports.destroy');

    Route::get('site-defaults/edit', [Controllers\CP\SiteDefaultsController::class, 'edit'])->name('site-defaults.edit');
    Route::patch('site-defaults', [Controllers\CP\SiteDefaultsController::class, 'update'])->name('site-defaults.update');

    Route::get('site-defaults/configure', [Controllers\CP\ConfigureSiteDefaultsController::class, 'edit'])->name('site-defaults.configure.edit');
    Route::patch('site-defaults/configure', [Controllers\CP\ConfigureSiteDefaultsController::class, 'update'])->name('site-defaults.configure.update');

    Route::get('section-defaults', [Controllers\CP\SectionDefaultsController::class, 'index'])->name('section-defaults.index');
    Route::get('section-defaults/collections/{seo_pro_collection}/edit', [Controllers\CP\CollectionDefaultsController::class, 'edit'])->name('section-defaults.collections.edit');
    Route::patch('section-defaults/collections/{seo_pro_collection}', [Controllers\CP\CollectionDefaultsController::class, 'update'])->name('section-defaults.collections.update');
    Route::get('section-defaults/taxonomies/{seo_pro_taxonomy}/edit', [Controllers\CP\TaxonomyDefaultsController::class, 'edit'])->name('section-defaults.taxonomies.edit');
    Route::patch('section-defaults/taxonomies/{seo_pro_taxonomy}', [Controllers\CP\TaxonomyDefaultsController::class, 'update'])->name('section-defaults.taxonomies.update');
});
