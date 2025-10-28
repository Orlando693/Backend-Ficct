<?php

namespace App\Http\Middleware;

use App\Services\BitacoraService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class BitacoraAutoLog
{
    public function __construct(private BitacoraService $bitacora) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            $this->tryLog($request, $response);
        } catch (Throwable $e) {
            // Tragamos errores de log para NO afectar la respuesta real
        }

        return $response;
    }

    private function tryLog(Request $request, Response $response): void
    {
        // Excluir rutas que no queremos loguear
        $path = $request->path(); // ej: "api/users"
        $skipPatterns = [
            'api/bitacora*',
            'sanctum/*',
            'api/docs*', 'docs*',
            'health*',
        ];
        if (Str::is($skipPatterns, $path)) return;

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 400) {
            // Si prefieres también loguear errores, comenta este return
            // y cambia la acción abajo a "ERROR (código)".
            return;
        }

        $method = strtoupper($request->method()); // GET/POST/...
        $module = $this->mapModule($path);
        $accion = $this->mapAction($method, $path);
        $desc   = sprintf('%s /%s', $method, $path);

        $this->bitacora->record([
            'modulo'      => $module,
            'accion'      => $accion,
            'descripcion' => $desc,
        ], $request);
    }

    private function mapModule(string $path): string
    {
        $p = ltrim($path, '/');
        if (Str::startsWith($p, 'api/auth'))        return 'AUTENTICACION';
        if (Str::startsWith($p, 'api/users') ||
            Str::startsWith($p, 'api/usuarios'))    return 'Usuarios';
        if (Str::startsWith($p, 'api/carreras'))    return 'Carreras';
        if (Str::startsWith($p, 'api/categorias'))  return 'Categorias';
        if (Str::startsWith($p, 'api/productos'))   return 'Productos';
        if (Str::startsWith($p, 'api/inventario'))  return 'Inventario';
        if (Str::startsWith($p, 'api/reportes'))    return 'Reportes';
        return 'General';
    }

    private function mapAction(string $method, string $path): string
    {
        $p = ltrim($path, '/');

        if (Str::startsWith($p, 'api/auth/login'))  return 'LOGIN';
        if (Str::startsWith($p, 'api/auth/logout')) return 'LOGOUT';

        return match ($method) {
            'POST'          => 'CREAR',
            'PUT', 'PATCH'  => 'EDITAR',
            'DELETE'        => 'ELIMINAR',
            default         => 'CONSULTAR',
        };
    }
}
