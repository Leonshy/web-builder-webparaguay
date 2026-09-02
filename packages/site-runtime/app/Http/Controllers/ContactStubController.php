<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Stub del envío del formulario de contacto (Tarea 2).
 *
 * El captcha, la validación real, el rate limiting y el envío son del
 * SISTEMA y no se configuran desde el JSON. Acá sólo se acusa recibo para
 * poder ver el flujo completo en /preview.
 */
class ContactStubController extends Controller
{
    public function __invoke(Request $request)
    {
        return back()->with('contacto_enviado', true);
    }
}
