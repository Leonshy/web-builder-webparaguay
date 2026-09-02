<?php

use App\Http\Controllers\ContactStubController;
use App\Http\Controllers\PreviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/preview'));

// Stub del formulario de contacto. El envío real es del sistema (Tarea posterior).
Route::post('/contacto', ContactStubController::class)->name('contact.submit');

// Rutas de desarrollo. Renderizan JSON validado, sin base de datos.
Route::get('/preview/{slug?}', fn ($slug = null) => app(PreviewController::class)->render('preview', '/preview', $slug))
    ->where('slug', '[a-z0-9-]+')->name('preview');

// Galería de las 41 variantes para la regresión visual con playwright.
Route::get('/variants/{slug?}', fn ($slug = null) => app(PreviewController::class)->render('variants', '/variants', $slug))
    ->where('slug', '[a-z0-9-]+')->name('variants');
