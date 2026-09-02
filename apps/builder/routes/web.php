<?php

use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('projects.index'));

Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
