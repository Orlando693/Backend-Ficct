<?php

namespace App\Http\Controllers\Api\Jefatura;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class GestionesController extends Controller
{
    public function index()
    {
        $exists = DB::selectOne("SELECT to_regclass('academia.gestion_academica') AS reg");
        if (!$exists || !$exists->reg) {
            return response()->json(['data' => []]);
        }

        $rows = DB::table(DB::raw('academia.gestion_academica'))
            ->select('id_gestion', 'anio', 'periodo', 'fecha_inicio', 'fecha_fin', 'estado')
            ->orderByDesc('anio')
            ->orderByDesc('periodo')
            ->get();

        return response()->json(['data' => $rows]);
    }
}
