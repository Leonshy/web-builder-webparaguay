<?php

use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('projects.index'));

Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');

use App\Http\Controllers\InterviewController;

Route::prefix('projects/{project}/interview')->group(function () {
    Route::get('/', [InterviewController::class, 'show'])->name('interview');
    Route::post('/brand', [InterviewController::class, 'saveBrand'])->name('interview.brand');
    Route::post('/brand/regenerate', [InterviewController::class, 'regeneratePalettes'])->name('interview.brand.regenerate');
    Route::post('/purpose', [InterviewController::class, 'savePurpose'])->name('interview.purpose');
    Route::post('/content', [InterviewController::class, 'saveContent'])->name('interview.content');
    Route::post('/reopen/{stage}', [InterviewController::class, 'reopen'])->name('interview.reopen');
    Route::post('/generate', [InterviewController::class, 'generate'])->name('interview.generate');
    Route::get('/result', [InterviewController::class, 'result'])->name('interview.result');
});

use App\Http\Controllers\PublishController;

Route::get('/projects/{project}/publish', [PublishController::class, 'show'])->name('publish.show');
Route::post('/projects/{project}/publish', [PublishController::class, 'store'])->name('publish.store');
