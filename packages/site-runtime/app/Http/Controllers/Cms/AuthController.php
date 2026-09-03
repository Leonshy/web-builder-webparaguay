<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Login del CMS. Cada instancia sirve un solo sitio y tiene su(s) dueño(s)
 * en la tabla `users`, que el builder crea al publicar (API interna).
 */
class AuthController extends Controller
{
    public function show()
    {
        if (Auth::check()) {
            return redirect()->route('cms.index');
        }

        return view('cms.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($data, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'Usuario o contraseña incorrectos.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('cms.index'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
