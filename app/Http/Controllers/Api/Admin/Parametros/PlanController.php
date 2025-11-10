<?php

namespace App\Http\Controllers\Api\Admin\Parametros;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parametros\StorePlanRequest;
use App\Support\Bitacora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlanController extends Controller
{
    public function index(Request $req)
    {
        $carreraId = (int) $req->query('carrera_id', 0);
        $sql = "SELECT * FROM academia.vw_plan_resumen";
        $params = [];

        if ($carreraId > 0) {
            $sql .= " WHERE carrera_id = ?";
            $params[] = $carreraId;
        }
        $sql .= " ORDER BY plan DESC, semestre ASC";

        return response()->json(['data' => DB::select($sql, $params)]);
    }

    public function store(StorePlanRequest $req)
    {
        $d = $req->validated();

        $c = DB::selectOne("SELECT 1 FROM academia.carreras WHERE id_carrera=? LIMIT 1", [$d['carrera_id']]);
        $m = DB::selectOne("SELECT 1 FROM academia.materia  WHERE id_materia=? LIMIT 1",  [$d['materia_id']]);
        if (!$c || !$m) {
            return response()->json(['message'=>'Carrera o Materia inexistente'], 422);
        }

        $row = DB::selectOne(<<<'SQL'
          INSERT INTO academia.materia_carrera(carrera_id, materia_id, plan, semestre, tipo, carga_teo, carga_pra)
          VALUES(?,?,?,?,?,?,?)
          ON CONFLICT (carrera_id, materia_id, plan) DO UPDATE
            SET semestre=EXCLUDED.semestre, tipo=EXCLUDED.tipo, carga_teo=EXCLUDED.carga_teo, carga_pra=EXCLUDED.carga_pra
          RETURNING id_materia_carrera
        SQL, [$d['carrera_id'],$d['materia_id'],$d['plan'],$d['semestre'],$d['tipo'],$d['carga_teo'],$d['carga_pra']]);

        Bitacora::log(optional(auth()->user())->id_persona, 'CPD', 'Plan', 'guardar', "Plan:{$row->id_materia_carrera}", 'OK', null, $d);

        return response()->json(['data' => $this->findPlan($row->id_materia_carrera)], 201);
    }

    public function destroy($id)
    {
        DB::delete("DELETE FROM academia.materia_carrera WHERE id_materia_carrera=?", [$id]);
        Bitacora::log(optional(auth()->user())->id_persona, 'CPD', 'Plan', 'eliminar', "Plan:$id");
        return response()->json(['ok' => true]);
    }

    private function findPlan($id)
    {
        return DB::selectOne("SELECT * FROM academia.vw_plan_resumen WHERE id_materia_carrera = ?", [$id]);
    }
}
