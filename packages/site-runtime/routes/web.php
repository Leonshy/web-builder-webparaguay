<?php

use App\Http\Controllers\Cms\CmsController;
use App\Http\Controllers\Cms\CmsPageController;
use App\Http\Controllers\Cms\CmsPreviewController;
use App\Http\Controllers\Cms\CmsSectionController;
use App\Http\Controllers\ContactStubController;
use App\Http\Controllers\Internal\CreateSiteController;
use App\Http\Controllers\Internal\MarkPublishedController;
use App\Http\Controllers\PreviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/cms'));

// Stub del formulario de contacto. El envío real es del sistema (Tarea posterior).
Route::post('/contacto', ContactStubController::class)->name('contact.submit');

// Rutas de desarrollo. Renderizan JSON de archivo validado, sin base de datos.
Route::get('/preview/{slug?}', fn ($slug = null) => app(PreviewController::class)->render('preview', '/preview', $slug))
    ->where('slug', '[a-z0-9-]+')->name('preview');
Route::get('/variants/{slug?}', fn ($slug = null) => app(PreviewController::class)->render('variants', '/variants', $slug))
    ->where('slug', '[a-z0-9-]+')->name('variants');

// Preview con token: no indexable, compartible. Renderiza desde la base.
Route::get('/s/{token}/{slug?}', CmsPreviewController::class)
    ->where('slug', '[a-z0-9-]+')->name('cms.preview');

// API interna: apps/builder entrega el documento generado y validado.
Route::post('/internal/sites', CreateSiteController::class)->name('internal.sites.create');
Route::post('/internal/sites/{site}/publish', MarkPublishedController::class)->name('internal.sites.publish');

// CMS. Formularios derivados del esquema; toda escritura valida antes de guardar.
Route::prefix('cms')->group(function () {
    Route::get('/', [CmsController::class, 'index'])->name('cms.index');

    Route::get('/sites/{site}', [CmsController::class, 'show'])->name('cms.site');
    Route::put('/sites/{site}', [CmsController::class, 'update'])->name('cms.site.update');
    Route::post('/sites/{site}/pages', [CmsController::class, 'storePage'])->name('cms.site.pages.store');
    Route::post('/sites/{site}/preview-tokens', [CmsController::class, 'storePreviewToken'])->name('cms.site.preview-tokens.store');

    Route::get('/pages/{page}', [CmsPageController::class, 'show'])->name('cms.page');
    Route::put('/pages/{page}', [CmsPageController::class, 'update'])->name('cms.page.update');
    Route::delete('/pages/{page}', [CmsPageController::class, 'destroy'])->name('cms.page.destroy');
    Route::post('/pages/{page}/sections', [CmsSectionController::class, 'store'])->name('cms.page.sections.store');

    Route::get('/sections/{section}', [CmsSectionController::class, 'show'])->name('cms.section');
    Route::put('/sections/{section}', [CmsSectionController::class, 'update'])->name('cms.section.update');
    Route::put('/sections/{section}/variant', [CmsSectionController::class, 'changeVariant'])->name('cms.section.variant');
    Route::post('/sections/{section}/move', [CmsSectionController::class, 'move'])->name('cms.section.move');
    Route::delete('/sections/{section}', [CmsSectionController::class, 'destroy'])->name('cms.section.destroy');
});
