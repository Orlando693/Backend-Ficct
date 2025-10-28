<?php

namespace App\Http\Controllers\Api\Carreras;

use App\Http\Controllers\Controller;
use App\Http\Requests\Carreras\StoreCarreraRequest;
use App\Http\Requests\Carreras\UpdateCarreraRequest;
use App\Models\Carrera;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CarreraController extends Controller
{
    // GET /api/carreras?with=count
    public function index(Request $request)
    {
        $withCount = $request->boolean('with') && $request->query('with') === 'count';

        $query = Carrera::query();

        if ($withCount) {
            // Si tienes relaciones definidas:
            $query->withCount(['materias as materias_count', 'grupos as grupos_count']);
        }

        $rows = $query->orderBy('nombre')->get();

        $data = $rows->map(fn ($c) => $this->mapRow($c, $withCount))->values();

        return response()->json(['data' => $data]);
    }

    // GET /api/carreras/{id}
    public function show($id, Request $request)
    {
        $withCount = $request->boolean('with') && $request->query('with') === 'count';
        $c = Carrera::query()->findOrFail($id);
        return response()->json(['data' => $this->mapRow($c, $withCount)]);
    }

    // POST /api/carreras
    public function store(StoreCarreraRequest $request)
    {
        $data = $request->validated();

        $carrera = Carrera::create([
            'nombre' => $data['nombre'],
            'sigla'  => mb_strtoupper($data['sigla']),
            'estado' => $data['estado'] ?? 'ACTIVA',
        ]);

        // Inicialmente contadores a 0
        return response()->json([
            'data' => $this->mapRow($carrera, withCount: false),
        ], 201);
    }

    // PUT /api/carreras/{id}
    public function update($id, UpdateCarreraRequest $request)
    {
        $carrera = Carrera::findOrFail($id);

        $data = $request->validated();
        if (isset($data['nombre'])) $carrera->nombre = $data['nombre'];
        if (isset($data['sigla']))  $carrera->sigla  = mb_strtoupper($data['sigla']);
        if (isset($data['estado'])) $carrera->estado = $data['estado'];

        $carrera->save();

        return response()->json(['data' => $this->mapRow($carrera, withCount: false)]);
    }

    // PATCH /api/carreras/{id}/estado
    public function setEstado($id, Request $request)
    {
        $validated = $request->validate([
            'estado' => ['required', Rule::in(['ACTIVA', 'INACTIVA'])],
        ]);

        $carrera = Carrera::findOrFail($id);
        $carrera->estado = $validated['estado'];
        $carrera->save();

        return response()->json(['data' => $this->mapRow($carrera, withCount: false)]);
    }

    // --- Helpers ---

    private function mapRow(Carrera $c, bool $withCount): array
    {
        // si no usas withCount, puedes calcular counts manuales (try/catch por si no hay relaciones)
        $materias = 0;
        $grupos   = 0;

        if ($withCount) {
            $materias = (int) ($c->materias_count ?? 0);
            $grupos   = (int) ($c->grupos_count ?? 0);
        } else {
            try { $materias = method_exists($c, 'materias') ? $c->materias()->count() : 0; } catch (\Throwable $e) {}
            try { $grupos   = method_exists($c, 'grupos')   ? $c->grupos()->count()   : 0; } catch (\Throwable $e) {}
        }

        return [
            'id' => $c->carrera_id,
            'nombre' => $c->nombre,
            'sigla' => $c->sigla,
            'estado' => $c->estado, // 'ACTIVA' | 'INACTIVA'
            'materiasAsociadas' => $materias,
            'gruposAsociados'   => $grupos,
        ];
    }
}
