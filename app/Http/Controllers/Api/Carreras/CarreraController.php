<?php

namespace App\Http\Controllers\Api\Carreras;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Carrera;

class CarreraController extends Controller
{
    // GET /api/carreras
    public function index(Request $request)
    {
        $q = Carrera::query();

        if ($estado = $request->query('estado')) {
            $q->where('estado', $estado);
        }

        // ignoramos "with=count" para evitar errores por vistas/relaciones
        $items = $q->orderBy('nombre', 'asc')->get()->map(fn($c) => $this->format($c));

        return response()->json(['data' => $items]);
    }

    // GET /api/carreras/{id}
    public function show($id)
    {
        return response()->json(['data' => $this->format(Carrera::findOrFail($id))]);
    }

    // POST /api/carreras
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required','string','max:120'],
            'sigla'  => [
                'required','string','max:15',
                Rule::unique(Carrera::class,'sigla'),
            ],
            'estado' => ['sometimes','string', Rule::in(['ACTIVA','INACTIVA'])],
        ]);

        $data['estado'] = $data['estado'] ?? 'ACTIVA';

        $c = Carrera::create($data);
        return response()->json(['data' => $this->format($c)], 201);
    }

    // PUT /api/carreras/{id}
    public function update(Request $request, $id)
    {
        $c = Carrera::findOrFail($id);

        $data = $request->validate([
            'nombre' => ['required','string','max:120'],
            'sigla'  => [
                'required','string','max:15',
                Rule::unique(Carrera::class,'sigla')->ignore($id),
            ],
            'estado' => ['sometimes','string', Rule::in(['ACTIVA','INACTIVA'])],
        ]);

        $c->update($data);
        return response()->json(['data' => $this->format($c)]);
    }

    // PATCH /api/carreras/{id}/estado
    public function setEstado(Request $request, $id)
    {
        $c = Carrera::findOrFail($id);
        $data = $request->validate([
            'estado' => ['required', Rule::in(['ACTIVA','INACTIVA'])],
        ]);
        $c->estado = $data['estado'];
        $c->save();

        return response()->json(['data' => $this->format($c)]);
    }

    private function format(Carrera $c): array
    {
        return [
            'id_carrera' => (int) $c->id,
            'nombre' => $c->nombre,
            'sigla' => $c->sigla,
            'estado' => $c->estado,
        ];
    }
}
