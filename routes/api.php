<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Usuarios\UsuarioController;
use App\Http\Controllers\Api\Reportes\ReporteController;
use App\Http\Controllers\Api\Carreras\CarreraController;

Route::prefix('auth')->group(function () {
    Route::post('/login', LoginController::class);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', LogoutController::class);
        Route::get('/me', MeController::class);
    });
});

Route::middleware(['auth:sanctum', 'can:manage-users'])
    ->prefix('users')
    ->group(function () {
        Route::get('/', [UsuarioController::class, 'index']);
        Route::post('/', [UsuarioController::class, 'store']);
        Route::put('/{id}', [UsuarioController::class, 'update']);
        Route::patch('/{id}/role', [UsuarioController::class, 'changeRole']);
        Route::patch('/{id}/toggle-block', [UsuarioController::class, 'toggleBlock']);
        Route::delete('/{id}', [UsuarioController::class, 'destroy']);
    });

Route::middleware(['auth:sanctum'])
    ->prefix('reportes')
    ->group(function () {
        Route::get('/docentes', [ReporteController::class, 'docentes']);
        Route::post('/generar', [ReporteController::class, 'generar']);
    });
    
Route::middleware(['auth:sanctum']) // si tienes policy, puedes añadir: 'can:manage-careers'
    ->prefix('carreras')
    ->group(function () {
        Route::get('/',        [CarreraController::class, 'index']);       // lista
        Route::get('/{id}',    [CarreraController::class, 'show']);        // una carrera (opcional)
        Route::post('/',       [CarreraController::class, 'store']);       // crear
        Route::put('/{id}',    [CarreraController::class, 'update']);      // editar
        Route::patch('/{id}/estado', [CarreraController::class, 'setEstado']); // activar/inactivar
        // (opcional) borrar
        // Route::delete('/{id}', [CarreraController::class, 'destroy']);
    });

Route::get('/health/ping', fn () => ['ok' => true]);
