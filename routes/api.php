<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BoletoController;
use App\Http\Controllers\IncidenteController;
use App\Http\Controllers\AutobusController;
use App\Http\Controllers\CorridaController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\VerificationController;
//use App\Http\Controllers\VerificationController;

// Autobuses
Route::post('/autobus/create', [AutobusController::class, 'create']);
Route::post('/autobus/update', [AutobusController::class, 'update']);
Route::post('/autobus/delete', [AutobusController::class, 'delete']);
Route::post('/autobus/view', [AutobusController::class, 'view']);

// Corridas
Route::post('/corrida/create', [CorridaController::class, 'create']);
Route::post('/corrida/update', [CorridaController::class, 'update']);
Route::post('/corrida/delete', [CorridaController::class, 'delete']);
Route::post('/corrida/view', [CorridaController::class, 'view']);
Route::post('/corrida/detalles', [CorridaController::class, 'detallesCorrida']);
Route::post('/corrida/search', [CorridaController::class, 'search']);

Route::post('/comprar-boleto', [BoletoController::class, 'comprarBoleto']);
Route::post('/cancelar-boleto', [BoletoController::class, 'cancelarBoleto']);
Route::post('/visualizar-boletos', [BoletoController::class, 'visualizarBoletos']);

Route::post('/registrar-incidente', [IncidenteController::class, 'registrarIncidente']);
// Asegúrate de que no esté dentro de un grupo con autenticación
Route::post('/register', [UserController::class, 'register'])->withoutMiddleware('auth:sanctum');



/*Route::get('/email/verify/{id}/{hash}', [UserController::class, 'verifyEmail'])
    ->middleware(['signed'])
    ->name('verification.verify');
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});*/

// Ruta para verificar el correo (sin autenticación)

Route::get('/verify-email/{id}/{hash}', [VerificationController::class, 'verify'])
    ->middleware(['auth', 'signed', 'throttle:6,1'])  // Aquí se agrega 'auth'
    ->name('verification.verify');

// Ruta para reenviar la verificación de correo
Route::post('/email/resend', [VerificationController::class, 'resend'])
    ->name('verification.resend');

Route::post('/registerU', [UserController::class, 'registerU']);
Route::post('/login', [UserController::class, 'login']);
Route::post('/verificarCorreo', [UserController::class, 'verificarCorreo']);
Route::post('/logout', [UserController::class, 'logout']);
Route::post('/detallesUsuario', [UserController::class, 'verDetallesUsuario']);
Route::post('/listaUsuarios', [UserController::class, 'listaUsuarios']);

Route::middleware('validate.token')->group(function () {
    Route::post('/update-user', [UserController::class, 'update']);
    Route::post('/delete-user', [UserController::class, 'delete']);
});

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

