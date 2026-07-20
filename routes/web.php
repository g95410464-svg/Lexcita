<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\AbogadoController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\ApiController;

Route::get('/', fn() => redirect()->route('login'));
Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
Route::post('/login',   [AuthController::class, 'login'])->name('login.post');
Route::get('/registro', [AuthController::class, 'showRegistro'])->name('registro');
Route::post('/registro',[AuthController::class, 'registro'])->name('registro.post');
Route::post('/logout',  [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'rol:cliente'])->prefix('cliente')->name('cliente.')->group(function () {
    Route::get('/dashboard',       [ClienteController::class, 'dashboard'])->name('dashboard');
    Route::get('/nueva-cita',      [ClienteController::class, 'nuevaCita'])->name('nueva-cita');
    Route::post('/nueva-cita',     [ClienteController::class, 'crearCita'])->name('nueva-cita.post');
    Route::get('/mis-citas',       [ClienteController::class, 'misCitas'])->name('mis-citas');
    Route::post('/cancelar/{id}',  [ClienteController::class, 'cancelarCita'])->name('cancelar');
});

Route::middleware(['auth', 'rol:abogado'])->prefix('abogado')->name('abogado.')->group(function () {
    Route::get('/dashboard', [AbogadoController::class, 'dashboard'])->name('dashboard');
    Route::get('/agenda',    [AbogadoController::class, 'agenda'])->name('agenda');
});

Route::middleware(['auth', 'rol:admin'])->prefix('interno')->name('interno.')->group(function () {
    Route::get('/dashboard',              [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/abogados',               [AdminController::class, 'abogados'])->name('abogados');
    Route::post('/abogados',              [AdminController::class, 'crearAbogado'])->name('abogados.crear');
    Route::patch('/abogados/{id}/toggle', [AdminController::class, 'toggleAbogado'])->name('abogados.toggle');
    Route::get('/clientes',               [AdminController::class, 'clientes'])->name('clientes');
    Route::get('/citas',                  [AdminController::class, 'citas'])->name('citas');
    Route::get('/estadisticas',           [AdminController::class, 'estadisticas'])->name('estadisticas');
    Route::post('/citas/{id}/confirmar',  [PagoController::class, 'confirmarManual'])->name('citas.confirmar');
    Route::post('/citas/{id}/cancelar',   [PagoController::class, 'cancelarManual'])->name('citas.cancelar');
});

Route::middleware(['auth'])->prefix('pago')->name('pago.')->group(function () {
    Route::get('/instrucciones/{citaId}', [PagoController::class, 'mostrarInstrucciones'])->name('instrucciones');
    Route::get('/exito',                  [PagoController::class, 'exito'])->name('exito');
    Route::get('/cancelado',              [PagoController::class, 'cancelado'])->name('cancelado');
    Route::get('/crear-sesion/{citaId}',  [PagoController::class, 'crearSesion'])->name('crear-sesion');
    Route::post('/citas/{id}/confirmar',  [PagoController::class, 'confirmarManual'])->name('confirmar');
});

Route::middleware(['auth'])->prefix('api')->name('api.')->group(function () {
    Route::get('/slots', [ApiController::class, 'slots'])->name('slots');
});