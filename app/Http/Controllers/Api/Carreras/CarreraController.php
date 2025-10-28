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
        $items = $q->orderBy('nombre', 'asc')->get();

        return response()->json(['data' => $items]);
    }

    // GET /api/carreras/{id}
    public function show($id)
    {
        return response()->json(Carrera::findOrFail($id));
    }

    // POST /api/carreras
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:120',
            'sigla'  => 'required|string|max:15|unique:academia.carreras,sigla',
            'codigo' => 'required|string|max:30|unique:academia.carreras,codigo',
            'estado' => ['sometimes','string', Rule::in(['ACTIVA','INACTIVA'])],
        ]);

        $data['estado'] = $data['estado'] ?? 'ACTIVA';

        $c = Carrera::create($data);
        return response()->json($c, 201);
    }

    // PUT /api/carreras/{id}
    public function update(Request $request, $id)
    {
        $c = Carrera::findOrFail($id);

        $data = $request->validate([
            'nombre' => 'required|string|max:120',
            'sigla'  => "required|string|max:15|unique:academia.carreras,sigla,$id,id_carrera",
            'codigo' => "required|string|max:30|unique:academia.carreras,codigo,$id,id_carrera",
            'estado' => ['sometimes','string', Rule::in(['ACTIVA','INACTIVA'])],
        ]);

        $c->update($data);
        return response()->json($c);
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

        return response()->json($c);
    }
}
