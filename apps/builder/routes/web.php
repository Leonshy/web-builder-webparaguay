<?php

use App\Http\Controllers\InterviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PublishController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check()
    ? redirect()->route('projects.index')
    : redirect()->route('login'));

// Alias para las vistas de Breeze (navegación, perfil).
Route::get('/dashboard', fn () => redirect()->route('projects.index'))
    ->middleware(['auth', 'verified'])->name('dashboard');

// Todo el builder requiere sesión y email verificado (control de abuso, §5.9).
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');

    Route::middleware(\App\Http\Middleware\EnsureProjectOwner::class)->group(function () {
        Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
        Route::get('/projects/{project}/cms', [ProjectController::class, 'cms'])->name('projects.cms');

        Route::prefix('projects/{project}/interview')->group(function () {
        Route::get('/', [InterviewController::class, 'show'])->name('interview');
        Route::post('/brand', [InterviewController::class, 'saveBrand'])->name('interview.brand');
        Route::post('/brand/regenerate', [InterviewController::class, 'regeneratePalettes'])->name('interview.brand.regenerate');
        Route::post('/purpose', [InterviewController::class, 'savePurpose'])->name('interview.purpose');
        Route::post('/content', [InterviewController::class, 'saveContent'])->name('interview.content');
        Route::post('/reopen/{stage}', [InterviewController::class, 'reopen'])->name('interview.reopen');
        Route::post('/generate', [InterviewController::class, 'generate'])
            ->middleware('throttle:generation')->name('interview.generate');
        Route::get('/result', [InterviewController::class, 'result'])->name('interview.result');
    });

        Route::get('/projects/{project}/publish', [PublishController::class, 'show'])->name('publish.show');
        Route::post('/projects/{project}/publish/billing', [PublishController::class, 'saveBilling'])->name('publish.billing');
        Route::post('/projects/{project}/publish', [PublishController::class, 'store'])->name('publish.store');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
