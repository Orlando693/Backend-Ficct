<?php

namespace App\Http\Controllers\Api\Jefatura;

use App\Http\Controllers\Controller;
use App\Http\Requests\Grupos\StoreGrupoRequest;
use App\Http\Requests\Grupos\UpdateGrupoRequest;
use App\Services\GrupoService;
use Illuminate\Http\Request;

class GruposController extends Controller
{
    public function __construct(private GrupoService $srv) {}

    // GET /api/jefatura/grupos?gestion_id=&materia_id=&estado=&search=&page=&limit=
    public function index(Request $r)
    {
        return response()->json($this->srv->list($r->all()));
    }

    // GET /api/jefatura/grupos/mini?gestion_id=...
    public function mini(Request $r)
    {
        $gestionId = $r->integer('gestion_id');
        return response()->json($this->srv->mini($gestionId ?: null));
    }

    // POST /api/jefatura/grupos
    public function store(StoreGrupoRequest $r)
    {
        return response()->json($this->srv->create($r->validated()), 201);
    }

    // PUT /api/jefatura/grupos/{id}
    public function update(UpdateGrupoRequest $r, int $id)
    {
        return response()->json($this->srv->update($id, $r->validated()));
    }

    // PATCH /api/jefatura/grupos/{id}/estado
    public function toggleEstado(int $id)
    {
        return response()->json($this->srv->toggleEstado($id));
    }
}
