<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\AbogadoController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApiController;

Route::get('/', fn() => redirect()->route('login'));
Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
Route::post('/login',   [AuthController::class, 'login'])->name('login.post');
Route::get('/registro', [AuthController::class, 'showRegistro'])->name('registro');
Route::post('/registro',[AuthController::class, 'registro'])->name('registro.post');
Route::post('/logout',  [AuthController::class, 'logout'])->name('logout');

Route::get('/auth/google/redirect', [AuthController::class, 'googleRedirect'])->name('google.redirect');
Route::get('/auth/google/callback', [AuthController::class, 'googleCallback'])->name('google.callback');

Route::middleware(['auth', 'verified', 'rol:cliente'])->prefix('cliente')->name('cliente.')->group(function () {
    Route::get('/dashboard',       [ClienteController::class, 'dashboard'])->name('dashboard');
    Route::get('/nueva-cita',      [ClienteController::class, 'nuevaCita'])->name('nueva-cita');
    Route::post('/nueva-cita',     [ClienteController::class, 'crearCita'])->name('nueva-cita.post');
    Route::get('/mis-citas',       [ClienteController::class, 'misCitas'])->name('mis-citas');
    Route::get('/ticket/{id}',     [ClienteController::class, 'ticket'])->name('ticket');
    Route::get('/hacer-pago/{id}', [ClienteController::class, 'hacerPago'])->name('hacer-pago');
    Route::get('/pre-confirmacion/{id}', [ClienteController::class, 'preConfirmacion'])->name('pre-confirmacion');
    Route::get('/procesar-pago/{id}', [ClienteController::class, 'procesarPago'])->name('cliente.procesar-pago');
    Route::get('/paypal-pago/{id}', [ClienteController::class, 'paypalPago'])->name('paypal-pago');
    Route::get('/paypal/checkout/{id}', [ClienteController::class, 'checkout'])->name('paypal.checkout');
    Route::get('/paypal/return',         [ClienteController::class, 'capture'])->name('paypal.return');
    Route::get('/paypal/cancel',         [ClienteController::class, 'cancel'])->name('paypal.cancel');
    Route::post('/paypal/create/{id}',  [ClienteController::class, 'crearOrdenAjax'])->name('paypal.create');
    Route::post('/paypal/capture/{id}', [ClienteController::class, 'capturarOrdenAjax'])->name('paypal.capture');
    Route::post('/cancelar/{id}',  [ClienteController::class, 'cancelarCita'])->name('cancelar');
});

// Ruta para crear sesión de pago (para citas pendientes de pago)
Route::get('/pago/crear-sesion/{id}', [ClienteController::class, 'procesarPago'])->name('pago.crear-sesion');

Route::middleware(['auth', 'verified', 'rol:abogado'])->prefix('abogado')->name('abogado.')->group(function () {
    Route::get('/dashboard', [AbogadoController::class, 'dashboard'])->name('dashboard');
    Route::get('/agenda',    [AbogadoController::class, 'agenda'])->name('agenda');
});

Route::middleware(['auth', 'verified', 'rol:admin'])->prefix('interno')->name('interno.')->group(function () {
    Route::get('/dashboard',              [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/abogados',               [AdminController::class, 'abogados'])->name('abogados');
    Route::post('/abogados',              [AdminController::class, 'crearAbogado'])->name('abogados.crear');
    Route::patch('/abogados/{id}/toggle', [AdminController::class, 'toggleAbogado'])->name('abogados.toggle');
    Route::get('/clientes',               [AdminController::class, 'clientes'])->name('clientes');
    Route::get('/citas',                  [AdminController::class, 'citas'])->name('citas');
    Route::get('/estadisticas',           [AdminController::class, 'estadisticas'])->name('estadisticas');
    Route::post('/citas/{id}/confirmar',  [AdminController::class, 'confirmarCita'])->name('citas.confirmar');
    Route::post('/citas/{id}/cancelar',   [AdminController::class, 'cancelarCita'])->name('citas.cancelar');
});

Route::middleware(['auth'])->prefix('api')->name('api.')->group(function () {
    Route::get('/slots', [ApiController::class, 'slots'])->name('slots');
});