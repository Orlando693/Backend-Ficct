<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Usuarios\UsuarioController;
use App\Http\Controllers\Api\Reportes\ReporteController;
use App\Http\Controllers\Api\Carreras\CarreraController;
use App\Http\Controllers\Api\Bitacora\BitacoraController;
use App\Http\Controllers\Api\Jefatura\MateriasController;
use App\Http\Controllers\Api\Jefatura\GruposController;
use App\Http\Controllers\Api\Jefatura\GestionesController as JefaturaGestionesController;
use App\Http\Controllers\Api\Aulas\AulaController;
use App\Http\Controllers\Api\Admin\Parametros\ParametrosController;
use App\Http\Controllers\Api\Admin\Parametros\GestionesController as ParametrosGestionesController;
use App\Http\Controllers\Api\Admin\Parametros\PlanController;
use App\Http\Controllers\Api\Admin\Parametros\ImportOfertaController;
use App\Http\Controllers\Api\Jefatura\ProgramacionController;

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
        // Route::delete('/{id}', [CarreraController::class, 'destroy']);   // opcional
    });

Route::middleware(['auth:sanctum'])
    ->prefix('bitacora')
    ->group(function () {
        Route::get('/',          [BitacoraController::class, 'index']);     // lista (array)
        Route::post('/',         [BitacoraController::class, 'store']);     // registrar
        Route::delete('/',       [BitacoraController::class, 'clearAll']);  // limpiar todo (ruta oficial)
        Route::delete('/clear',  [BitacoraController::class, 'clearAllFallback']); // fallback para tu front
        Route::delete('/{id}',   [BitacoraController::class, 'destroy']);   // eliminar 1
    });

Route::prefix('materias')->/*middleware('auth:sanctum')->*/group(function () {
    Route::get('/',              [MateriasController::class, 'index']);        // lista con filtros + resumen
    Route::get('/mini',          [MateriasController::class, 'mini']);         // dropdown
    Route::post('/',             [MateriasController::class, 'store']);        // crear
    Route::match(['put','patch'],'/{id}', [MateriasController::class, 'update']); // editar
    Route::patch('/{id}/estado', [MateriasController::class, 'setEstado']);    // activar / inactivar
    Route::delete('/{id}',       [MateriasController::class, 'destroy']);      // eliminar
});

/* ← Esta era la línea que rompía: apuntaba a un GestionesController sin alias */
Route::get('/gestiones', [ParametrosGestionesController::class, 'index']);

Route::prefix('grupos')->/*middleware('auth:sanctum')->*/group(function () {
    Route::get('/',              [GruposController::class, 'index']);
    Route::get('/mini',          [GruposController::class, 'mini']);
    Route::post('/',             [GruposController::class, 'store']);
    Route::put('/{id}',          [GruposController::class, 'update']);
    Route::patch('/{id}/estado', [GruposController::class, 'toggleEstado']);
    Route::delete('/{id}',       [GruposController::class, 'destroy']);
});

Route::middleware(['auth:api', 'bitacora.auto'])->prefix('jefatura')->group(function () {
    Route::get( 'grupos',               [GruposController::class, 'index' ]);
    Route::get( 'grupos/mini',          [GruposController::class, 'mini'  ]);
    Route::post('grupos',               [GruposController::class, 'store' ]);
    Route::put( 'grupos/{id}',          [GruposController::class, 'update']);
    Route::patch('grupos/{id}/estado',  [GruposController::class, 'toggleEstado']);
    Route::delete('grupos/{id}',        [GruposController::class, 'destroy']);
});

Route::prefix('aulas')->/*middleware('auth:sanctum')->*/group(function () {
    Route::get('/',              [AulaController::class, 'index']);
    Route::post('/',             [AulaController::class, 'store']);
    Route::put('/{id}',          [AulaController::class, 'update']);
    Route::patch('/{id}/estado', [AulaController::class, 'setEstado']);
});

Route::prefix('parametros-academicos')->group(function () {
    Route::get('/', [ParametrosController::class, 'show']);
    Route::put('/', [ParametrosController::class, 'update']);
});

Route::prefix('gestiones')->group(function () {
    Route::get('/', [ParametrosGestionesController::class, 'index']);
    Route::post('/', [ParametrosGestionesController::class, 'store']);
    Route::put('/{id}', [ParametrosGestionesController::class, 'update']);
    Route::delete('/{id}', [ParametrosGestionesController::class, 'destroy']);
});

Route::prefix('plan-estudios')->group(function () {
    Route::get('/', [PlanController::class, 'index']);
    Route::post('/', [PlanController::class, 'store']);
    Route::delete('/{id}', [PlanController::class, 'destroy']);
});

Route::prefix('importar/oferta')->group(function () {
    Route::post('/preview', [ImportOfertaController::class, 'preview']);
    Route::post('/confirm', [ImportOfertaController::class, 'confirm']);
});
Route::prefix('programacion')/*->middleware(['auth:api','bitacora.auto'])*/->group(function () {
    Route::get(   '/horarios',            [ProgramacionController::class, 'horariosIndex']);
    Route::post(  '/horarios',            [ProgramacionController::class, 'horariosStore']);
    Route::delete('/horarios/{id}',       [ProgramacionController::class, 'horariosDestroy']);
});
Route::options('/{any}', fn() => response()->noContent())
    ->where('any', '.*');

Route::get('/health/ping', fn () => ['ok' => true]);
