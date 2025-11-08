<?php

namespace App\Http\Controllers\Api\Aulas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Aulas\StoreAulaRequest;
use App\Http\Requests\Aulas\UpdateAulaRequest;
use App\Http\Resources\Aulas\AulaResource;
use App\Models\Aula;
use App\Traits\LogsToBitacora;
use Illuminate\Http\Request;

class AulaController extends Controller
{
    use LogsToBitacora;

    public function __construct()
    {
        // Requiere login y rol CPD (puedes añadir ADMIN si quieres permitir ambos)
        $this->middleware(['auth:sanctum']);
        // Si ya tienes un middleware 'role', úsalo; sino, validamos adentro.
        // $this->middleware('role:CPD,ADMIN');
    }

    // GET /api/aulas
    public function index(Request $request)
    {
        // Fallback de rol si no tienes middleware 'role'
        $user = $request->user();
        if (!in_array(strtoupper($user->rol ?? ''), ['CPD','ADMIN'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $q          = trim((string) $request->get('q', ''));
        $estado     = $request->get('estado'); // ACTIVA/INACTIVA o null
        $tipo       = $request->get('tipo');   // TEORIA/LABORATORIO o null
        $edificioId = $request->get('edificio_id'); // opcional
        $perPage    = (int) ($request->get('per_page', 15));

        $query = Aula::query()
            ->when($q !== '', fn($qq) =>
                $qq->where(function($w) use ($q) {
                    $w->where('codigo', 'ilike', "%{$q}%");
                })
            )
            ->when($estado, fn($qq) => $qq->where('estado', $estado))
            ->when($tipo,   fn($qq) => $qq->where('tipo', $tipo))
            ->when($edificioId, fn($qq) => $qq->where('edificio_id', $edificioId))
            ->orderBy('codigo', 'asc');

        $page = $query->paginate($perPage);

        // Log de consulta (no bloqueante)
        $this->logBitacora($request, 'Aulas', 'listar', 'Aula', 'Listado de aulas', [
            'q'=>$q,'estado'=>$estado,'tipo'=>$tipo,'edificio_id'=>$edificioId
        ]);

        return AulaResource::collection($page)->additional([
            'meta' => [
                'total' => $page->total(),
                'per_page' => $page->perPage(),
                'current_page' => $page->currentPage(),
            ]
        ]);
    }

    // POST /api/aulas
    public function store(StoreAulaRequest $request)
    {
        $user = $request->user();
        if (!in_array(strtoupper($user->rol ?? ''), ['CPD','ADMIN'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $aula = Aula::create($request->validated());

        $this->logBitacora($request, 'Aulas', 'crear', 'Aula', "Creó aula {$aula->codigo}");

        return (new AulaResource($aula))
            ->additional(['message' => 'Aula creada correctamente'])
            ->response()
            ->setStatusCode(201);
    }

    // PUT /api/aulas/{id}
    public function update(UpdateAulaRequest $request, int $id)
    {
        $user = $request->user();
        if (!in_array(strtoupper($user->rol ?? ''), ['CPD','ADMIN'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $aula = Aula::findOrFail($id);
        $aula->update($request->validated());

        $this->logBitacora($request, 'Aulas', 'editar', 'Aula', "Editó aula {$aula->codigo}");

        return (new AulaResource($aula->fresh()))
            ->additional(['message' => 'Aula actualizada']);
    }

    // PATCH /api/aulas/{id}/estado
    public function setEstado(Request $request, int $id)
    {
        $user = $request->user();
        if (!in_array(strtoupper($user->rol ?? ''), ['CPD','ADMIN'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $request->validate([
            'estado' => ['required','in:ACTIVA,INACTIVA'],
        ]);

        $aula = Aula::findOrFail($id);
        $aula->estado = $request->estado;
        $aula->save();

        $this->logBitacora($request, 'Aulas', 'estado', 'Aula', "Cambió estado de {$aula->codigo} a {$aula->estado}");

        return (new AulaResource($aula))
            ->additional(['message' => 'Estado actualizado']);
    }
}
