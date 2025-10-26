<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Usuarios\UsuarioController; // <<--- aquí

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

Route::get('/health/ping', fn () => ['ok' => true]);
