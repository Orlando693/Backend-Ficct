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

    // GET /api/aulas
    public function index(Request $request)
    {
        $q          = trim((string) $request->get('q', ''));
        $estado     = $request->get('estado');       // 'activo'|'inactivo'|'ACTIVA'|'INACTIVA'
        $tipo       = $request->get('tipo');         // 'teoria'|'laboratorio'|'TEORIA'|'LABORATORIO'
        $edificioId = $request->get('edificio_id');  // opcional
        $perPage    = (int) ($request->get('per_page', 15)) ?: 15;

        // Normalizar estado
        if (is_string($estado)) {
            $estado = strtoupper($estado);
            if ($estado === 'ACTIVO')   $estado = 'ACTIVA';
            if ($estado === 'INACTIVO') $estado = 'INACTIVA';
        }
        // Normalizar tipo
        if (is_string($tipo)) $tipo = strtolower($tipo);

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
        $aula = Aula::findOrFail($id);
        $aula->update($request->validated());

        $this->logBitacora($request, 'Aulas', 'editar', 'Aula', "Editó aula {$aula->codigo}");

        return (new AulaResource($aula->fresh()))
            ->additional(['message' => 'Aula actualizada']);
    }

    // PATCH /api/aulas/{id}/estado
    public function setEstado(Request $request, int $id)
    {
        // Aceptar 'activo'/'inactivo' y mapear al enum de DB
        $estado = strtoupper((string) $request->input('estado'));
        if ($estado === 'ACTIVO')   $estado = 'ACTIVA';
        if ($estado === 'INACTIVO') $estado = 'INACTIVA';

        $request->merge(['estado' => $estado]);

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
