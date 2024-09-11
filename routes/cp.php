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

Route::prefix('seo-pro/links')->group(function () {
    Route::get('/', [Controllers\Linking\LinksController::class, 'index'])->name('seo-pro.internal-links.index');
    Route::get('/filter', [Controllers\Linking\LinksController::class, 'filter'])->name('seo-pro.entry-links.index');

    Route::prefix('link')->group(function () {
        Route::get('/{link}', [Controllers\Linking\LinksController::class, 'getLink'])->name('seo-pro.entry-links.get-link');
        Route::put('/{link}', [Controllers\Linking\LinksController::class, 'updateLink'])->name('seo-pro.entry-links.update-link');
        Route::delete('/{link}', [Controllers\Linking\LinksController::class, 'resetEntrySuggestions'])->name('seo-pro.entry-links.reset-suggesions');
    });

    Route::get('/overview', [Controllers\Linking\LinksController::class, 'getOverview'])->name('seo-pro.entry-links.overview');
    Route::get('/{entryId}/suggestions', [Controllers\Linking\LinksController::class, 'getSuggestions'])->name('seo-pro.internal-links.get-suggestions');
    Route::get('/{entryId}/related', [Controllers\Linking\LinksController::class, 'getRelatedContent'])->name('seo-pro.internal-links.related');
    Route::get('/{entryId}/internal', [Controllers\Linking\LinksController::class, 'getInternalLinks'])->name('seo-pro.internal-links.internal');
    Route::get('/{entryId}/external', [Controllers\Linking\LinksController::class, 'getExternalLinks'])->name('seo-pro.internal-links.external');
    Route::get('/{entryId}/inbound', [Controllers\Linking\LinksController::class, 'getInboundInternalLinks'])->name('seo-pro.internal-links.inbound');
    Route::get('/{entryId}/sections', [Controllers\Linking\LinksController::class, 'getSections'])->name('seo-pro.internal-links.sections');

    Route::get('/field-details/{entryId}/{fieldPath}', [Controllers\Linking\LinksController::class, 'getLinkFieldDetails'])->name('seo-pro.internal-links.field-details');

    Route::post('/', [Controllers\Linking\LinksController::class, 'insertLink'])->name('seo-pro.internal-links.insert-link');
    Route::post('/check', [Controllers\Linking\LinksController::class, 'checkLinkReplacement'])->name('seo-pro.internal-links.check-link');

    Route::post('/ignored-suggestions', [Controllers\Linking\IgnoredSuggestionsController::class, 'create'])->name('seo-pro.ignored-suggestions.create');

    Route::prefix('/config')->group(function () {
        Route::prefix('/collections')->group(function () {
            Route::get('/', [Controllers\Linking\CollectionLinkSettingsController::class, 'index'])->name('seo-pro.internal-link-settings.collections');
            Route::put('/{collection}', [Controllers\Linking\CollectionLinkSettingsController::class, 'update'])->name('seo-pro.internal-link-settings.collections.update');
            Route::delete('/{collection}', [Controllers\Linking\CollectionLinkSettingsController::class, 'resetConfig'])->name('seo-pro.internal-link-settings.collections.delete');
        });

        Route::prefix('/sites')->group(function () {
            Route::get('/', [Controllers\Linking\SiteLinkSettingsController::class, 'index'])->name('seo-pro.internal-link-settings.sites');
            Route::put('/{site}', [Controllers\Linking\SiteLinkSettingsController::class, 'update'])->name('seo-pro.internal-link-settings.sites.update');
            Route::delete('/{site}', [Controllers\Linking\SiteLinkSettingsController::class, 'resetConfig'])->name('seo-pro.internal-link-settings.sites.reset');
        });
    });

    Route::prefix('/automatic')->group(function () {
        Route::get('/', [Controllers\Linking\GlobalAutomaticLinksController::class, 'index'])->name('seo-pro.automatic-links.index');
        Route::get('/filter', [Controllers\Linking\GlobalAutomaticLinksController::class, 'filter'])->name('seo-pro.automatic-links.filter');

        Route::post('/', [Controllers\Linking\GlobalAutomaticLinksController::class, 'create'])->name('seo-pro.automatic-links.create');
        Route::delete('/{automaticLink}', [Controllers\Linking\GlobalAutomaticLinksController::class, 'delete'])->name('seo-pro.automatic-links.delete');
        Route::post('/{automaticLink}', [Controllers\Linking\GlobalAutomaticLinksController::class, 'update'])->name('seo-pro.automatic-links.update');
    });
});