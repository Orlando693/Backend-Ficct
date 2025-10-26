<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Users\UserController;

// === AUTH ===
Route::prefix('auth')->group(function () {
    Route::post('/login', LoginController::class);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', LogoutController::class);
        Route::get('/me', MeController::class);
    });
});

// === USERS (solo CPD/Decanato) ===
Route::middleware(['auth:sanctum', 'can:manage-users'])->prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::patch('/{id}/role', [UserController::class, 'changeRole']);
    Route::patch('/{id}/toggle-block', [UserController::class, 'toggleBlock']);
    Route::delete('/{id}', [UserController::class, 'destroy']);
});
