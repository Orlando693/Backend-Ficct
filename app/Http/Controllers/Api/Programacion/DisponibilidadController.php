<?php

namespace App\Http\Controllers\Api\Programacion;

use App\Http\Controllers\Controller;
use App\Support\Bitacora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DisponibilidadController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'gestion_id'    => ['required','integer','min:1'],
            'dia_semana'    => ['required','integer','between:1,7'],
            'hora_inicio'   => ['required','date_format:H:i'],
            'hora_fin'      => ['required','date_format:H:i','after:hora_inicio'],
            'min_capacidad' => ['nullable','integer','min:1'],
            'tipo'          => ['nullable','string','max:20'],
            'grupo_id'      => ['nullable','integer','min:1'],
        ]);

        $horaInicio = $data['hora_inicio'].':00';
        $horaFin    = $data['hora_fin'].':00';
        $minCap     = $data['min_capacidad'] ?? null;
        $tipo       = $data['tipo'] ? strtoupper($data['tipo']) : null;
        $grupoExcl  = $data['grupo_id'] ?? null;

        $aulas = DB::select("
            SELECT
              a.id_aula,
              a.codigo AS numero,
              a.tipo,
              a.capacidad,
              a.estado
            FROM academia.aula a
            WHERE a.estado = 'ACTIVA'
              AND (:capacidad IS NULL OR a.capacidad >= :capacidad)
              AND (:tipo IS NULL OR UPPER(a.tipo) = :tipo)
              AND NOT EXISTS (
                SELECT 1
                FROM academia.horario h
                JOIN academia.grupo g ON g.id_grupo = h.grupo_id
                WHERE g.gestion_id = :gestion
                  AND h.aula_id = a.id_aula
                  AND h.dia_semana = :dia
                  AND (:ini < h.hora_fin) AND (:fin > h.hora_inicio)
                  AND (:grupo IS NULL OR h.grupo_id <> :grupo)
              )
            ORDER BY a.capacidad, a.codigo
        ", [
            'capacidad' => $minCap,
            'tipo'      => $tipo,
            'gestion'   => $data['gestion_id'],
            'dia'       => $data['dia_semana'],
            'ini'       => $horaInicio,
            'fin'       => $horaFin,
            'grupo'     => $grupoExcl,
        ]);

        Bitacora::log(optional($request->user())->id_persona, 'Jefatura', 'Programacion', 'disponibilidad', 'Aulas', 'OK', null, $data);

        $payload = array_map(function ($row) {
            return [
                'id_aula'   => (int) $row->id_aula,
                'numero'    => $row->numero,
                'tipo'      => strtolower($row->tipo ?? 'teoria'),
                'capacidad' => (int) $row->capacidad,
                'estado'    => strtolower($row->estado ?? '') === 'activa' ? 'activo' : 'inactivo',
            ];
        }, $aulas);

        return response()->json([
            'data' => $payload,
        ]);
    }
}
