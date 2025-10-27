<?php

namespace App\Http\Controllers\Api\Reportes;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ReportesService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReporteController extends Controller
{
    public function __construct(private ReportesService $service)
    {
        //
    }

    /**
     * GET /api/reportes/docentes
     * Docentes provenientes de "Gestionar Usuario" (role=Docente).
     * Puedes filtrar por estado si quieres: ?estado=ACTIVO
     */
    public function docentes(Request $request)
    {
        $estado = $request->query('estado'); // ACTIVO | BLOQUEADO | PENDIENTE | INACTIVO | null

        $query = User::query()->where('role', 'Docente');
        if ($estado) {
            $query->where('status', $estado);
        }

        $docentes = $query->orderBy('name')
            ->get(['id','name'])
            ->map(fn($u) => ['id' => $u->id, 'nombre' => $u->name])
            ->values();

        return response()->json(['data' => $docentes]);
    }

    /**
     * POST /api/reportes/generar
     * Body JSON:
     * {
     *   "tipo":"horarios|carga|asistencia|aulas", "gestion":"2024-2",
     *   "desde":?, "hasta":?, "carrera":?, "materia":?, "docente":?, "grupo":?, "aula":?, "turno":"Mañana|Tarde|Noche|"
     * }
     */
    public function generar(Request $request)
    {
        $data = $request->validate([
            'tipo'    => ['required', Rule::in(['horarios','carga','asistencia','aulas'])],
            'gestion' => ['required','string','max:20'],
            'desde'   => ['nullable','date'],
            'hasta'   => ['nullable','date','after_or_equal:desde'],
            'carrera' => ['nullable','string','max:120'],
            'materia' => ['nullable','string','max:120'],
            'docente' => ['nullable','string','max:150'],
            'grupo'   => ['nullable','string','max:50'],
            'aula'    => ['nullable','string','max:50'],
            'turno'   => ['nullable', Rule::in(['Mañana','Tarde','Noche',''])],
        ]);

        // Por ahora generamos datos tipo "demo" (igual que el front). Luego: consultar BD.
        $rows = $this->service->generar($data);

        return response()->json([
            'filtros' => $data,
            'data'    => $rows,
        ]);
    }
}
