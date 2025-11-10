<?php

namespace App\Http\Controllers\Api\Programacion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DisponibilidadController extends Controller
{
    public function index(Request $r)
    {
        $data = $r->validate([
            'gestion_id'    => 'required|integer',
            'dia'           => 'required|integer|min:1|max:7',
            'inicio'        => 'nullable|date_format:H:i',
            'fin'           => 'nullable|date_format:H:i|after:inicio',
            'turno'         => 'nullable|in:manana,tarde,noche',
            'capacidad_min' => 'nullable|integer|min:1',
            'tipo'          => 'nullable|string', // 'teoria','laboratorio', etc.
        ]);

        // Si no mandan inicio/fin, mapear por turno desde gestion_parametros
        if ((!$r->filled('inicio') || !$r->filled('fin')) && $r->filled('turno')) {
            $row = DB::selectOne("
              SELECT
                 CASE WHEN :t='manana' THEN hora_manana_ini
                      WHEN :t='tarde'  THEN hora_tarde_ini
                      ELSE                 hora_noche_ini
                 END AS ini,
                 CASE WHEN :t='manana' THEN hora_manana_fin
                      WHEN :t='tarde'  THEN hora_tarde_fin
                      ELSE                 hora_noche_fin
                 END AS fin
              FROM academia.gestion_parametros
              WHERE gestion_id = :gid
              LIMIT 1",
              ['gid'=>$data['gestion_id'],'t'=>$r->turno]
            );
            if ($row) { $data['inicio'] = $row->ini; $data['fin'] = $row->fin; }
        }

        if (!isset($data['inicio']) || !isset($data['fin'])) {
            return response()->json(['message'=>'Debe enviar inicio/fin o turno'], 422);
        }

        $aulas = DB::select("
            SELECT a.id_aula, a.codigo, a.capacidad, a.tipo
            FROM academia.aula a
            WHERE a.estado='ACTIVA'
              AND (:cap IS NULL OR a.capacidad >= :cap)
              AND (:tipo IS NULL OR a.tipo = :tipo)
              AND NOT EXISTS (
                 SELECT 1
                 FROM academia.horario h
                 JOIN academia.grupo g ON g.id_grupo = h.grupo_id
                 WHERE g.gestion_id = :gid
                   AND h.aula_id = a.id_aula
                   AND h.dia_semana = :dia
                   AND (:ini < h.hora_fin) AND (:fin > h.hora_inicio)
              )
            ORDER BY a.capacidad, a.codigo",
            [
              'gid'=>$data['gestion_id'], 'dia'=>$data['dia'],
              'ini'=>$data['inicio'], 'fin'=>$data['fin'],
              'cap'=>$r->capacidad_min, 'tipo'=>$r->tipo
            ]
        );

        // Bitácora (opcional)
        DB::select("SELECT academia.fn_log_bitacora(?, ?, ?, ?, ?, ?, ?, ?)", [
            optional($r->user())->id_persona, 'Jefatura',
            'Programación', 'CONSULTAR', 'Disponibilidad',
            'OK', null, json_encode($data)
        ]);

        return response()->json([
            'filtros' => $data,
            'aulas_disponibles' => $aulas,
        ]);
    }
}
