<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

use App\Http\Middleware\BitacoraAutoLog;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Tu middleware para bitácora en el grupo API
        $middleware->appendToGroup('api', [
            BitacoraAutoLog::class,
        ]);

        // CORS global
        $middleware->use([
            HandleCors::class,
        ]);

        // 🔒 Clave: NO redirigir invitados a 'login' (no existe en API)
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Siempre renderizar JSON en el namespace /api/*
        $exceptions->shouldRenderJsonWhen(function (Request $request) {
            return $request->is('api/*') || $request->expectsJson();
        });

        // ✅ Captura la falta de autenticación y responde 401 JSON (sin route('login'))
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            return response()->json(['message' => 'Unauthenticated'], 401);
        });

        // (Opcional) Evita 500 si alguna librería intenta usar una ruta inexistente
        $exceptions->render(function (RouteNotFoundException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Not Found'], 404);
            }
            return response()->json(['message' => 'Not Found'], 404);
        });
    })
    ->create();
