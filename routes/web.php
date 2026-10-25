<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\AbogadoController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\VideoRoomController;
use App\Http\Controllers\SliPublicController;
use App\Http\Controllers\SliEditorController;
use App\Http\Controllers\SliLibraryController;

// Reject malformed identifiers before PHP type coercion or database queries.
Route::pattern('id', '[0-9]+');

Route::get('/', function () {
    if (config('organization.id') === 'servicios-legales-integrados') {
        return view('sli.landing');
    }

    return redirect()->route('login');
})->middleware('throttle:portal')->name('home');
Route::middleware('throttle:portal')->name('sli.')->group(function () {
    Route::get('/sli-media/{file}', [SliEditorController::class, 'media'])->where('file', '[a-f0-9-]{36}\.(jpg|jpeg|png|webp)')->name('media');
    Route::get('/agendar', [SliPublicController::class, 'book'])->name('start');
    Route::get('/conocenos', [SliPublicController::class, 'about'])->name('about');
    Route::get('/como-funciona', [SliPublicController::class, 'how'])->name('how');
    Route::get('/abogados', [SliPublicController::class, 'lawyers'])->name('lawyers');
    Route::get('/abogados/{id}', [SliPublicController::class, 'profile'])->name('profile');
    Route::get('/abogados/{id}/agendar', [SliPublicController::class, 'book'])->name('book');
});
Route::get('/login',    [AuthController::class, 'showLogin'])->middleware('throttle:portal')->name('login');
Route::post('/login',   [AuthController::class, 'login'])->middleware('throttle:login')->name('login.post');
Route::get('/registro', [AuthController::class, 'showRegistro'])->middleware('throttle:portal')->name('registro');
if (config('organization.id') === 'servicios-legales-integrados') {
    Route::post('/registro',[AuthController::class, 'registro'])->middleware(['throttle:register', 'throttle:otp-start'])->name('registro.post');
    Route::get('/registro/verificar', [AuthController::class, 'showRegistrationVerification'])->middleware('throttle:portal')->name('registro.verificar');
    Route::post('/registro/verificar', [AuthController::class, 'verifyRegistration'])->middleware('throttle:otp-verify')->name('registro.verificar.post');
    Route::post('/registro/verificar/reenviar', [AuthController::class, 'resendRegistrationVerification'])->middleware('throttle:otp-resend')->name('registro.verificar.resend');
    Route::get('/registro/google/telefono', [AuthController::class, 'showGoogleRegistrationPhone'])->middleware(['throttle:portal', 'throttle:otp-start'])->name('registro.google.telefono');
} else {
    Route::post('/registro',[AuthController::class, 'registro'])->middleware('throttle:register')->name('registro.post');
}
Route::post('/logout',  [AuthController::class, 'logout'])->name('logout');

Route::get('/auth/google/redirect', [AuthController::class, 'googleRedirect'])->middleware('throttle:oauth')->name('google.redirect');
Route::get('/auth/google/callback', [AuthController::class, 'googleCallback'])->middleware('throttle:oauth')->name('google.callback');

Route::middleware(['auth', 'verified', 'rol:cliente', 'throttle:portal'])->prefix('cliente')->name('cliente.')->group(function () {
    Route::get('/dashboard',       [ClienteController::class, 'dashboard'])->name('dashboard');
    Route::get('/nueva-cita',      [ClienteController::class, 'nuevaCita'])->name('nueva-cita');
    Route::post('/nueva-cita',     [ClienteController::class, 'crearCita'])->middleware('throttle:booking')->name('nueva-cita.post');
    Route::get('/mis-citas',       [ClienteController::class, 'misCitas'])->name('mis-citas');
    Route::get('/cambios-horario', [\App\Http\Controllers\ReschedulingController::class, 'index'])->name('agenda.cambios');
    Route::post('/propuestas/{proposal}/aceptar', [\App\Http\Controllers\ReschedulingController::class, 'accept'])->middleware('throttle:writes')->name('agenda.aceptar');
    Route::post('/propuestas/{proposal}/responder', [\App\Http\Controllers\ReschedulingController::class, 'respond'])->middleware('throttle:writes')->name('agenda.responder');
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
    Route::get('/agenda/eventos', [\App\Http\Controllers\AgendaController::class, 'feed'])->name('agenda.eventos');
    Route::post('/agenda/eventos', [\App\Http\Controllers\AgendaController::class, 'storeEvent'])->middleware('throttle:writes')->name('agenda.eventos.store');
    Route::patch('/agenda/eventos/{event}', [\App\Http\Controllers\AgendaController::class, 'updateEvent'])->middleware('throttle:writes')->name('agenda.eventos.update');
    Route::delete('/agenda/eventos/{event}', [\App\Http\Controllers\AgendaController::class, 'destroyEvent'])->middleware('throttle:writes')->name('agenda.eventos.destroy');
    Route::post('/agenda/ubicaciones', [\App\Http\Controllers\AgendaController::class, 'saveLocation'])->middleware('throttle:writes')->name('agenda.ubicaciones');
    Route::patch('/agenda/ubicaciones/{location}', [\App\Http\Controllers\AgendaController::class, 'saveLocation'])->middleware('throttle:writes')->name('agenda.ubicaciones.update');
    Route::post('/agenda/preferencias', [\App\Http\Controllers\AgendaController::class, 'saveSettings'])->middleware('throttle:writes')->name('agenda.preferencias');
    Route::post('/agenda/trayectos', [\App\Http\Controllers\AgendaController::class, 'saveManualRoute'])->middleware('throttle:writes')->name('agenda.trayectos');
    Route::get('/agenda/conflictos/{conflict}/opciones', [\App\Http\Controllers\AgendaController::class, 'suggestions'])->name('agenda.opciones');
    Route::post('/agenda/conflictos/{conflict}/propuestas', [\App\Http\Controllers\AgendaController::class, 'sendProposal'])->middleware('throttle:writes')->name('agenda.propuestas');
    Route::post('/agenda/propuestas/{proposal}/retirar', [\App\Http\Controllers\ReschedulingController::class, 'withdraw'])->middleware('throttle:writes')->name('agenda.retirar');
    Route::get('/biblioteca', [SliLibraryController::class, 'index'])->name('biblioteca');
    Route::get('/biblioteca/{document}/leer', [SliLibraryController::class, 'read'])->name('biblioteca.read');
    Route::get('/biblioteca/{document}/contenido', [SliLibraryController::class, 'content'])->name('biblioteca.content');
    Route::get('/biblioteca/{document}/anotaciones', [SliLibraryController::class, 'annotations'])->name('biblioteca.annotations');
    Route::post('/biblioteca/{document}/anotaciones', [SliLibraryController::class, 'annotate'])->middleware('throttle:writes')->name('biblioteca.annotate');
    Route::delete('/biblioteca/{document}/anotaciones/{annotation}', [SliLibraryController::class, 'removeAnnotation'])->middleware('throttle:writes')->name('biblioteca.annotations.destroy');
});

Route::middleware(['auth', 'verified', 'rol:admin', 'throttle:portal'])->prefix('interno')->name('interno.')->group(function () {
    Route::get('/agenda/conflictos',[\App\Http\Controllers\AgendaController::class,'administrator'])->name('agenda.conflictos');
    Route::get('/biblioteca', [SliLibraryController::class, 'manage'])->name('biblioteca');
    Route::post('/biblioteca', [SliLibraryController::class, 'store'])->middleware('throttle:writes')->name('biblioteca.store');
    Route::delete('/biblioteca/{document}', [SliLibraryController::class, 'destroy'])->middleware('throttle:writes')->name('biblioteca.destroy');
    Route::get('/personalizar', [SliEditorController::class, 'index'])->name('personalizar');
    Route::put('/personalizar', [SliEditorController::class, 'update'])->middleware('throttle:writes')->name('personalizar.update');
    Route::put('/personalizar/abogados/{id}', [SliEditorController::class, 'updateLawyer'])->middleware('throttle:writes')->name('personalizar.abogado');
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
    Route::post('/booking-holds', [ApiController::class, 'retenerHorario'])->middleware(['rol:cliente', 'throttle:booking'])->name('booking-holds.create');
    Route::delete('/booking-holds', [ApiController::class, 'liberarHorario'])->middleware(['rol:cliente', 'throttle:writes'])->name('booking-holds.delete');
});

Route::post('/agenda/avisos/{notification}/leido', [\App\Http\Controllers\AgendaController::class, 'readNotification'])
    ->whereNumber('notification')->middleware(['auth','verified','rol:cliente,abogado,admin','throttle:writes'])->name('agenda.avisos.leido');

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
