<?php

use App\Http\Controllers\PreviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/preview'));

// Ruta de desarrollo. Renderiza schema/example-site.json validado.
Route::get('/preview/{slug?}', PreviewController::class)
    ->where('slug', '[a-z0-9-]+')
    ->name('preview');
