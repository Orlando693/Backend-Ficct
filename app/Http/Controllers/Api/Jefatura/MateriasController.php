<?php

namespace App\Http\Controllers\Api\Jefatura;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Materias\StoreMateriaRequest;
use App\Http\Requests\Materias\UpdateMateriaRequest;
use App\Http\Requests\Materias\SetEstadoMateriaRequest;
use App\Services\MateriaService;

class MateriasController extends Controller
{
    public function __construct(private MateriaService $svc) {}

    // GET /api/materias?q=&estado=&page=&per_page=
    public function index(Request $req)
    {
        $out = $this->svc->list($req->all());
        return response()->json($out);
    }

    // GET /api/materias/mini
    public function mini()
    {
        return response()->json($this->svc->mini());
    }

    // POST /api/materias
    public function store(StoreMateriaRequest $req)
    {
        $out = $this->svc->create($req->validated());
        return response()->json($out, 201);
    }

    // PUT/PATCH /api/materias/{id}
    public function update($id, UpdateMateriaRequest $req)
    {
        $out = $this->svc->update((int)$id, $req->validated());
        return response()->json($out);
    }

    // PATCH /api/materias/{id}/estado
    public function setEstado($id, SetEstadoMateriaRequest $req)
    {
        $out = $this->svc->setEstado((int)$id, $req->validated()['estado']);
        return response()->json($out);
    }
}
