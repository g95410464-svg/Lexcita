<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Usuario;
use App\Services\GoogleLoginService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        // Auth usa guard 'web' con modelo Usuario (config/auth.php)
        if (!Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password'], 'activo' => true])) {
            return back()->withErrors(['email' => 'Credenciales incorrectas o cuenta inactiva.'])->withInput();
        }

        $request->session()->regenerate();

        return $this->redireccionPorRol(Auth::user());
    }

    public function showRegistro()
    {
        return view('auth.registro');
    }

    public function registro(Request $request)
    {
        $data = $request->validate([
            'nombre'             => 'required|string|max:120',
            'email'              => 'required|email|max:255|unique:usuarios,email',
            'password'           => 'required|string|min:8|confirmed',
            'telefono_whatsapp'  => 'required|string|max:20',
        ], [
            'nombre.required' => 'Ingresa tu nombre completo.',
            'email.unique' => 'Este correo ya está registrado. Inicia sesión con tu cuenta.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ], [
            'nombre' => 'nombre completo',
            'email' => 'correo electrónico',
            'password' => 'contraseña',
            'telefono_whatsapp' => 'teléfono de WhatsApp',
        ]);

        $usuario = Usuario::create([
            'nombre'            => $data['nombre'],
            'email'             => $data['email'],
            'password'          => Hash::make($data['password']),
            'rol'               => 'cliente',
            'telefono_whatsapp' => $data['telefono_whatsapp'],
            'activo'            => true,
        ]);

        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->route('cliente.dashboard');
    }

    public function googleRedirect(Request $request, GoogleLoginService $google)
    {
        if (!$google->configured()) {
            return redirect()->route('login')->withErrors(['email' => 'Google no está disponible. Ingresa con tu correo y contraseña.']);
        }

        $state = Str::random(64);
        $nonce = Str::random(64);
        $request->session()->put('google.oauth', [
            'state' => $state,
            'nonce' => $nonce,
            'expires_at' => now()->addMinutes(10)->timestamp,
            'redirect_uri' => config('google.redirect_uri'),
        ]);

        return redirect()->away($google->authorizationUrl($state, $nonce));
    }

    public function googleCallback(Request $request, GoogleLoginService $google)
    {
        // Consume once, including cancelled or malformed callbacks.
        $pending = $request->session()->pull('google.oauth');
        $state = $request->query('state');
        $code = $request->query('code');
        if (!is_array($pending)
            || !is_string($state) || strlen($state) !== 64
            || !hash_equals($pending['state'], $state)
            || $pending['expires_at'] <= now()->timestamp
            || $pending['redirect_uri'] !== config('google.redirect_uri')
            || $request->has('error')
            || !is_string($code) || $code === '' || strlen($code) > 8192) {
            return redirect()->route('login')->withErrors(['email' => 'La solicitud de Google venció o no es válida. Intenta iniciar sesión otra vez.']);
        }

        try {
            $identity = $google->identity($code, $pending['nonce']);
        } catch (\Exception $exception) {
            // Never log OAuth codes, tokens, provider responses or account details.
            Log::warning('Google login verification failed.', ['exception_type' => get_class($exception)]);
            return redirect()->route('login')->withErrors(['email' => 'No fue posible verificar tu cuenta de Google. Usa una cuenta Gmail o Google Workspace, o ingresa con correo y contraseña.']);
        }

        $usuario = Usuario::firstOrCreate(['email' => $identity['email']], [
            'nombre' => $identity['nombre'],
            'password' => Hash::make(Str::random(48)),
            'rol' => 'cliente',
            'telefono_whatsapp' => '',
            'activo' => true,
        ]);

        if (!$usuario->activo) {
            return redirect()->route('login')->withErrors(['email' => 'La cuenta está inactiva. Contacta con la administración.']);
        }

        Auth::login($usuario);
        $request->session()->regenerate();

        return $this->redireccionPorRol($usuario);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    private function redireccionPorRol(Usuario $usuario)
    {
        return match($usuario->rol) {
            'admin'   => redirect()->route('interno.dashboard'),
            'abogado' => redirect()->route('abogado.dashboard'),
            default   => redirect()->route('cliente.dashboard'),
        };
    }
}
