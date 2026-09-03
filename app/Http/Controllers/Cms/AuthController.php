<?php

namespace App\Http\Controllers\Cms;

use App\Cms\CmsSso;
use App\Http\Controllers\Controller;
use App\Models\User;
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

    /**
     * Acceso directo desde el builder: token HMAC firmado con el secreto
     * compartido. Sin token válido, cae al login normal.
     */
    public function sso(Request $request)
    {
        $data = CmsSso::verify((string) $request->query('t', ''));

        if ($data === null) {
            return redirect()->route('login')->withErrors(['email' => 'El enlace de acceso venció. Volvé a entrar desde la plataforma.']);
        }

        $user = User::firstOrCreate(
            ['email' => $data['email']],
            ['name' => $data['name'] ?? 'Dueño del sitio', 'password' => bin2hex(random_bytes(16))],
        );

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('cms.index');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
