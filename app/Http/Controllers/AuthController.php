<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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

        Log::info('LOGIN ATTEMPT', ['email' => $credentials['email']]);

        $attempt = Auth::attempt([
            'email'    => $credentials['email'],
            'password' => $credentials['password'],
            'activo'   => true,
        ]);

        Log::info('LOGIN RESULT', ['result' => $attempt]);

        if (!$attempt) {
            return back()->withErrors(['email' => 'Credenciales incorrectas o cuenta inactiva.'])->withInput();
        }

        $request->session()->regenerate();

        if (!Auth::user()->hasVerifiedEmail()) {
            Auth::logout();
            return back()->withErrors(['email' => 'Debes verificar tu correo electrónico antes de iniciar sesión.'])->withInput();
        }

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