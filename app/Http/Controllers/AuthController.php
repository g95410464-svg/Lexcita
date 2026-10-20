<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Usuario;
use App\Services\GoogleLoginService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\RegistrationOtpService;
use Throwable;

class AuthController extends Controller
{
    private const SLI_SUPPORT_ERROR = 'No pudimos completar la solicitud. Contacta con servicio técnico en g95410464@proton.me.';

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
        if (config('organization.id') === 'servicios-legales-integrados') {
            $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        }

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

        if (config('organization.id') === 'servicios-legales-integrados') {
            try {
                app(RegistrationOtpService::class)->begin($request->session()->getId(), $data);
            } catch (Throwable $exception) {
                Log::warning('SLI registration verification could not start.', ['exception_type' => get_class($exception)]);
                return back()->withErrors(['email' => $this->sliOtpError($exception, [
                    'Ya hay una verificación pendiente para ese correo. Usa el código enviado o espera a que venza.',
                    'Escribe el teléfono en formato internacional, por ejemplo +50371234567.',
                ])])
                    ->withInput($request->except('password', 'password_confirmation'));
            }

            return redirect()->route('registro.verificar')->with('status', 'Te enviamos un código a tu correo electrónico.');
        }

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

    public function showRegistrationVerification(RegistrationOtpService $otp)
    {
        abort_unless($otp->hasPending(request()->session()->getId()), 404);
        return view('auth.verificar', [
            'contacts' => $otp->pendingContacts(request()->session()->getId()),
        ]);
    }

    public function verifyRegistration(Request $request, RegistrationOtpService $otp)
    {
        $data = $request->validate([
            'email_code' => ['required', 'digits:6'],
        ]);

        try {
            $usuario = $otp->verify($request->session()->getId(), $data['email_code']);
        } catch (Throwable $exception) {
            Log::notice('SLI registration OTP verification failed.', ['exception_type' => get_class($exception)]);
            return back()->withErrors(['email_code' => $this->sliOtpError($exception, [
                'La verificación venció. Inicia el registro otra vez.',
                'Se agotaron los intentos. Solicita un código nuevo.',
                'El código no es correcto.',
            ])]);
        }

        Auth::login($usuario);
        $request->session()->regenerate();
        return $this->redireccionPorRol($usuario);
    }

    public function resendRegistrationVerification(Request $request, RegistrationOtpService $otp)
    {
        try {
            $otp->resend($request->session()->getId());
        } catch (Throwable $exception) {
            Log::notice('SLI registration OTP resend failed.', ['exception_type' => get_class($exception)]);
            return back()->withErrors(['email_code' => $this->sliOtpError($exception, [
                'La verificación venció. Inicia el registro otra vez.',
                'Espera un minuto antes de pedir otro código.',
            ])]);
        }
        return back()->with('status', 'Enviamos un código nuevo. Revisa tu correo electrónico.');
    }

    public function showGoogleRegistrationPhone(Request $request, RegistrationOtpService $otp)
    {
        $pending = $request->session()->get('sli.google_pending');
        abort_unless(is_array($pending) && isset($pending['email'], $pending['nombre']), 404);

        try {
            $otp->begin($request->session()->getId(), [
                'email' => $pending['email'],
                'nombre' => $pending['nombre'],
                'telefono_whatsapp' => null,
                'password' => Str::random(64),
            ]);
        } catch (Throwable $exception) {
            Log::warning('SLI Google email verification could not start.', ['exception_type' => get_class($exception)]);
            return redirect()->route('login')->withErrors(['email' => $this->sliOtpError($exception, [
                'Ya hay una verificación pendiente para ese correo. Usa el código enviado o espera a que venza.',
            ])]);
        }

        $request->session()->forget('sli.google_pending');
        return redirect()->route('registro.verificar')->with('status', 'Te enviamos un código al correo verificado de Google.');
    }

    public function startGoogleRegistrationVerification(Request $request, RegistrationOtpService $otp)
    {
        $pending = $request->session()->get('sli.google_pending');
        abort_unless(is_array($pending) && isset($pending['email'], $pending['nombre']), 404);

        $validated = $request->validate([
            'telefono_whatsapp' => ['required', 'string', 'max:20', 'regex:/^\\+?[0-9 ().-]{8,20}$/'],
        ]);
        $data = [
            'nombre' => $pending['nombre'],
            'email' => $pending['email'],
            'telefono_whatsapp' => $validated['telefono_whatsapp'],
            'password' => Str::random(64),
        ];

        try {
            $otp->begin($request->session()->getId(), $data);
        } catch (Throwable $exception) {
            Log::warning('SLI Google registration verification could not start.', ['exception_type' => get_class($exception)]);
            return back()->withErrors(['telefono_whatsapp' => $this->sliOtpError($exception, [
                'Ya hay una verificación pendiente para ese correo. Usa el código enviado o espera a que venza.',
                'Escribe el teléfono en formato internacional, por ejemplo +50371234567.',
            ])])->withInput();
        }

        $request->session()->forget('sli.google_pending');
        return redirect()->route('registro.verificar')->with('status', 'Confirma el correo de Google para terminar de crear la cuenta.');
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

        if (config('organization.id') === 'servicios-legales-integrados') {
            $usuario = Usuario::where('email', $identity['email'])->first();
            if (!$usuario) {
                try {
                    app(RegistrationOtpService::class)->begin($request->session()->getId(), [
                        'email' => $identity['email'],
                        'nombre' => $identity['nombre'],
                        'telefono_whatsapp' => null,
                        'password' => Str::random(64),
                    ]);
                } catch (Throwable $exception) {
                    Log::warning('SLI Google email verification could not start.', ['exception_type' => get_class($exception)]);
                    return redirect()->route('login')->withErrors(['email' => $this->sliOtpError($exception, [
                        'Ya hay una verificación pendiente para ese correo. Usa el código enviado o espera a que venza.',
                    ])]);
                }

                return redirect()->route('registro.verificar')->with('status', 'Te enviamos un código al correo verificado de Google.');
            }
        } else {
            $usuario = Usuario::firstOrCreate(['email' => $identity['email']], [
                'nombre' => $identity['nombre'],
                'password' => Hash::make(Str::random(48)),
                'rol' => 'cliente',
                'telefono_whatsapp' => '',
                'activo' => true,
            ]);
        }

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

    private function sliOtpError(Throwable $exception, array $safeMessages = []): string
    {
        if ($exception instanceof \RuntimeException && in_array($exception->getMessage(), $safeMessages, true)) {
            return $exception->getMessage();
        }

        return self::SLI_SUPPORT_ERROR;
    }
}
