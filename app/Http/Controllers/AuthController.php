<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Usuario;

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
            'email'              => 'required|email|unique:usuarios,email',
            'password'           => 'required|min:8|confirmed',
            'telefono_whatsapp'  => 'required|string|max:20',
        ]);

        $usuario = Usuario::create([
            'nombre'            => $data['nombre'],
            'email'             => $data['email'],
            'password'          => Hash::make($data['password']),
            'rol'               => 'cliente',
            'telefono_whatsapp' => $data['telefono_whatsapp'],
        ]);

        Auth::login($usuario);

        return redirect()->route('cliente.dashboard');
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
