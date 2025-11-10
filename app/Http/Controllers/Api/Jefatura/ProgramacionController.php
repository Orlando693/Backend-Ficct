<?php

namespace App\Http\Controllers\Api\Jefatura;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProgramacionController extends Controller
{
    /**
     * GET /programacion/horarios?gestion_id=..&grupo_id=?
     * Devuelve HorarioDTO[]: {id_horario, grupo_id, aula_id, aula_label, dia_semana, hora_inicio, hora_fin}
     */
    public function horariosIndex(Request $r)
    {
        $data = $r->validate([
            'gestion_id' => ['required','integer','min:1'],
            'grupo_id'   => ['nullable','integer','min:1'],
        ]);

        $q = DB::table('academia.horario AS h')
            ->join('academia.grupo AS g', 'g.id_grupo', '=', 'h.grupo_id')
            ->leftJoin('academia.aula  AS a', 'a.id_aula', '=', 'h.aula_id')
            ->where('g.gestion_id', $r->integer('gestion_id'))
            ->selectRaw("
                h.id_horario, h.grupo_id, h.aula_id, h.dia_semana,
                to_char(h.hora_inicio,'HH24:MI') AS hora_inicio,
                to_char(h.hora_fin,'HH24:MI')    AS hora_fin,
                COALESCE(a.codigo, 'Aula '||a.id_aula) AS aula_label
            ")
            ->orderBy('h.dia_semana')->orderBy('h.hora_inicio');

        if ($r->filled('grupo_id')) {
            $q->where('h.grupo_id', $r->integer('grupo_id'));
        }

        $rows = $q->get()->map(function ($row) {
            return [
                'id_horario' => (int) $row->id_horario,
                'grupo_id'   => (int) $row->grupo_id,
                'aula_id'    => (int) $row->aula_id,
                'dia_semana' => (int) $row->dia_semana,
                'hora_inicio'=> $row->hora_inicio,
                'hora_fin'   => $row->hora_fin,
                'aula_label' => $row->aula_label,
            ];
        });

        return response()->json(['data' => $rows]);
    }

    /**
     * POST /programacion/horarios
     * Body: { grupo_id, aula_id, dia_semana, hora_inicio, hora_fin }
     */
    public function horariosStore(Request $r)
    {
        $data = $r->validate([
            'grupo_id'    => ['required','integer','min:1'],
            'aula_id'     => ['required','integer','min:1'],
            'dia_semana'  => ['required','integer','between:1,7'],
            'hora_inicio' => ['required','string'], // "HH:MM"
            'hora_fin'    => ['required','string'],
        ]);

        // Normaliza HH:MM -> HH:MM:SS para PG
        $hi = preg_match('/^\d{2}:\d{2}$/', $data['hora_inicio']) ? $data['hora_inicio'].':00' : $data['hora_inicio'];
        $hf = preg_match('/^\d{2}:\d{2}$/', $data['hora_fin'])    ? $data['hora_fin'].':00'    : $data['hora_fin'];

        $id = DB::table('academia.horario')->insertGetId([
            'grupo_id'    => (int)$data['grupo_id'],
            'aula_id'     => (int)$data['aula_id'],
            'dia_semana'  => (int)$data['dia_semana'],
            'hora_inicio' => $hi,
            'hora_fin'    => $hf,
            'estado'      => 'ACTIVO',
        ], 'id_horario');

        // Bitácora (si tienes req->user()->id_persona)
        try {
            $personaId = optional($r->user())->id_persona;
            DB::select(
                "SELECT academia.fn_log_bitacora(?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $personaId, 'Jefatura', 'Programacion', 'crear',
                    'Horario:'.$id, 'OK', 'Crear bloque', json_encode($data)
                ]
            );
        } catch (\Throwable $e) {
            // no romper por logger
        }

        return response()->json(['data' => $this->formatHorario($id)], 201);
    }

    /**
     * DELETE /programacion/horarios/{id}
     */
    public function horariosDestroy($id, Request $r)
    {
        $row = DB::table('academia.horario')->where('id_horario', $id)->first();
        if (!$row) {
            return response()->json(['message'=>'No existe'], 404);
        }

        DB::table('academia.horario')->where('id_horario', $id)->delete();

        try {
            $personaId = optional($r->user())->id_persona;
            DB::select(
                "SELECT academia.fn_log_bitacora(?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $personaId, 'Jefatura', 'Programacion', 'eliminar',
                    'Horario:'.$id, 'OK', 'Eliminar bloque', json_encode(['id'=>$id])
                ]
            );
        } catch (\Throwable $e) {}

        return response()->json(['ok' => true]);
    }

    private function formatHorario(int $id): array
    {
        $row = DB::table('academia.horario AS h')
            ->leftJoin('academia.aula AS a', 'a.id_aula', '=', 'h.aula_id')
            ->where('h.id_horario', $id)
            ->selectRaw("
                h.id_horario,
                h.grupo_id,
                h.aula_id,
                h.dia_semana,
                to_char(h.hora_inicio,'HH24:MI') AS hora_inicio,
                to_char(h.hora_fin,'HH24:MI') AS hora_fin,
                COALESCE(a.codigo, 'Aula '||a.id_aula) AS aula_label
            ")
            ->first();

        return [
            'id_horario' => (int) $row->id_horario,
            'grupo_id'   => (int) $row->grupo_id,
            'aula_id'    => (int) $row->aula_id,
            'dia_semana' => (int) $row->dia_semana,
            'hora_inicio'=> $row->hora_inicio,
            'hora_fin'   => $row->hora_fin,
            'aula_label' => $row->aula_label,
        ];
    }
}
