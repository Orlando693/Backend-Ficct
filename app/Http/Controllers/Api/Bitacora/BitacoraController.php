<?php

namespace App\Http\Controllers\Api\Bitacora;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Bitacora;
use App\Services\BitacoraService;

class BitacoraController extends Controller
{
    // GET /api/bitacora  -> devuelve array plano (lo que espera tu front)
    public function index(Request $request)
    {
        $q = Bitacora::query();

        // Filtro opcional ?q=texto
        if ($text = trim((string) $request->query('q', ''))) {
            $q->where(function ($qq) use ($text) {
                $qq->where('modulo', 'ILIKE', "%$text%")
                   ->orWhere('accion', 'ILIKE', "%$text%")
                   ->orWhere('descripcion', 'ILIKE', "%$text%")
                   ->orWhere('usuario', 'ILIKE', "%$text%")
                   ->orWhere('ip', 'ILIKE', "%$text%");
            });
        }

        // Orden más reciente primero
        $rows = $q->orderByDesc('created_at')
                  ->orderByDesc('id')
                  ->limit((int) $request->query('per_page', 500)) // devuelve hasta 500 registros (front pagina en memoria)
                  ->get([
                      'id', 'modulo', 'accion', 'descripcion', 'usuario', 'ip', 'created_at'
                  ]);

        return response()->json($rows);
    }

    // POST /api/bitacora  -> registrar manualmente
    public function store(Request $request, BitacoraService $service)
    {
        $data = $request->validate([
            'modulo'      => 'required|string|max:80',
            'accion'      => 'required|string|max:50',
            'descripcion' => 'nullable|string|max:255',
            'usuario'     => 'nullable|string|max:120',
        ]);

        $row = $service->record($data, $request);
        return response()->json($row, 201);
    }

    // DELETE /api/bitacora/{id}
    public function destroy($id)
    {
        // Solo CPD o Decanato pueden borrar registros individuales
        $role = request()->user()?->role;
        if (!in_array($role, ['CPD', 'Decanato'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $row = Bitacora::findOrFail($id);
        $row->delete();

        return response()->json(['ok' => true]);
    }

    // DELETE /api/bitacora  (limpiar todo)
    public function clearAll()
    {
        $role = request()->user()?->role;
        if (!in_array($role, ['CPD', 'Decanato'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // TRUNCATE para reiniciar IDs
        DB::statement('TRUNCATE TABLE academia.bitacora RESTART IDENTITY');
        return response()->json(['ok' => true, 'message' => 'Bitácora vaciada']);
    }

    // Fallback: DELETE /api/bitacora/clear
    public function clearAllFallback()
    {
        return $this->clearAll();
    }
}
