<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\AbogadoController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\VideoRoomController;

// Reject malformed identifiers before PHP type coercion or database queries.
Route::pattern('id', '[0-9]+');

Route::get('/', fn() => redirect()->route('login'))->middleware('throttle:portal');
Route::get('/login',    [AuthController::class, 'showLogin'])->middleware('throttle:portal')->name('login');
Route::post('/login',   [AuthController::class, 'login'])->middleware('throttle:login')->name('login.post');
Route::get('/registro', [AuthController::class, 'showRegistro'])->middleware('throttle:portal')->name('registro');
Route::post('/registro',[AuthController::class, 'registro'])->middleware('throttle:register')->name('registro.post');
Route::post('/logout',  [AuthController::class, 'logout'])->name('logout');

Route::get('/auth/google/redirect', [AuthController::class, 'googleRedirect'])->middleware('throttle:oauth')->name('google.redirect');
Route::get('/auth/google/callback', [AuthController::class, 'googleCallback'])->middleware('throttle:oauth')->name('google.callback');

Route::middleware(['auth', 'verified', 'rol:cliente', 'throttle:portal'])->prefix('cliente')->name('cliente.')->group(function () {
    Route::get('/dashboard',       [ClienteController::class, 'dashboard'])->name('dashboard');
    Route::get('/nueva-cita',      [ClienteController::class, 'nuevaCita'])->name('nueva-cita');
    Route::post('/nueva-cita',     [ClienteController::class, 'crearCita'])->middleware('throttle:booking')->name('nueva-cita.post');
    Route::get('/mis-citas',       [ClienteController::class, 'misCitas'])->name('mis-citas');
    Route::get('/ticket/{id}',     [ClienteController::class, 'ticket'])->name('ticket');
    Route::get('/hacer-pago/{id}', [ClienteController::class, 'hacerPago'])->name('hacer-pago');
    Route::get('/pre-confirmacion/{id}', [ClienteController::class, 'preConfirmacion'])->name('pre-confirmacion');
    Route::get('/procesar-pago/{id}', [ClienteController::class, 'procesarPago'])->middleware('throttle:payments')->name('cliente.procesar-pago');
    Route::get('/paypal-pago/{id}', [ClienteController::class, 'paypalPago'])->name('paypal-pago');
    Route::get('/paypal/checkout/{id}', [ClienteController::class, 'checkout'])->middleware('throttle:payments')->name('paypal.checkout');
    Route::get('/paypal/return',         [ClienteController::class, 'capture'])->middleware('throttle:payments')->name('paypal.return');
    Route::get('/paypal/cancel',         [ClienteController::class, 'cancel'])->name('paypal.cancel');
    Route::post('/paypal/create/{id}',  [ClienteController::class, 'crearOrdenAjax'])->middleware('throttle:payments')->name('paypal.create');
    Route::post('/paypal/capture/{id}', [ClienteController::class, 'capturarOrdenAjax'])->middleware('throttle:payments')->name('paypal.capture');
    Route::post('/cancelar/{id}',  [ClienteController::class, 'cancelarCita'])->middleware('throttle:writes')->name('cancelar');
});

// Ruta para crear sesión de pago (para citas pendientes de pago)
Route::get('/pago/crear-sesion/{id}', [ClienteController::class, 'procesarPago'])->middleware(['auth', 'throttle:payments'])->name('pago.crear-sesion');

Route::middleware(['auth', 'verified', 'rol:abogado', 'throttle:portal'])->prefix('abogado')->name('abogado.')->group(function () {
    Route::get('/dashboard', [AbogadoController::class, 'dashboard'])->name('dashboard');
    Route::get('/agenda',    [AbogadoController::class, 'agenda'])->name('agenda');
});

Route::middleware(['auth', 'verified', 'rol:admin', 'throttle:portal'])->prefix('interno')->name('interno.')->group(function () {
    Route::get('/dashboard',              [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/abogados',               [AdminController::class, 'abogados'])->name('abogados');
    Route::post('/abogados',              [AdminController::class, 'crearAbogado'])->middleware('throttle:writes')->name('abogados.crear');
    Route::patch('/abogados/{id}/toggle', [AdminController::class, 'toggleAbogado'])->middleware('throttle:writes')->name('abogados.toggle');
    Route::get('/clientes',               [AdminController::class, 'clientes'])->name('clientes');
    Route::get('/citas',                  [AdminController::class, 'citas'])->name('citas');
    Route::get('/estadisticas',           [AdminController::class, 'estadisticas'])->name('estadisticas');
    Route::post('/citas/{id}/confirmar',  [AdminController::class, 'confirmarCita'])->middleware('throttle:writes')->name('citas.confirmar');
    Route::post('/citas/{id}/cancelar',   [AdminController::class, 'cancelarCita'])->middleware('throttle:writes')->name('citas.cancelar');
});

Route::middleware(['auth', 'throttle:slots'])->prefix('api')->name('api.')->group(function () {
    Route::get('/slots', [ApiController::class, 'slots'])->name('slots');
});

// Sala de videollamada: accesible por cliente Y abogado de la misma cita.
// La autorización se resuelve server-side en VideoRoomController::salaAccesible
// (VideoRoomService::validarAcceso). Ruta única GET compartida: video.sala.
Route::middleware(['auth', 'verified', 'throttle:video'])->prefix('videollamada')->name('video.')->group(function () {
    Route::get('/{roomToken}',                [VideoRoomController::class, 'show'])->name('sala');
    Route::post('/{roomToken}/offer',         [VideoRoomController::class, 'offer'])->name('offer');
    Route::post('/{roomToken}/answer',        [VideoRoomController::class, 'answer'])->name('answer');
    Route::post('/{roomToken}/ice',           [VideoRoomController::class, 'ice'])->name('ice');
    Route::post('/{roomToken}/leave',         [VideoRoomController::class, 'leave'])->name('leave');
});
