<?php

namespace App\Http\Controllers\Api\Programacion;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;

class HorariosController extends Controller
{
    public function index(Request $req)
    {
        $q = DB::table('academia.vw_horario_resumen as h');

        if ($req->filled('grupo_id')) {
            $q->where('h.grupo_id', $req->integer('grupo_id'));
        }

        return $q->orderBy('dia_semana')->orderBy('hora_inicio')->get();
    }

    public function store(Request $req)
    {
        // Validación fuerte
        $data = $req->validate([
            'grupo_id'   => ['required','integer'],
            'aula_id'    => ['required','integer'],
            'dia_semana' => ['required','integer','between:1,7'],
            'hora_inicio'=> ['required','date_format:H:i'],
            'hora_fin'   => ['required','date_format:H:i','after:hora_inicio'],
        ]);

        // Verifica existencia explícita (así devolvemos 422 legible, no 500)
        $okGrupo = DB::table('academia.grupo')->where('id_grupo', $data['grupo_id'])->exists();
        $okAula  = DB::table('academia.aula')->where('id_aula',  $data['aula_id'])->exists();

        if (!$okGrupo) {
            throw ValidationException::withMessages(['grupo_id' => 'El grupo no existe.']);
        }
        if (!$okAula) {
            throw ValidationException::withMessages(['aula_id' => 'El aula no existe.']);
        }

        try {
            $id = DB::table('academia.horario')->insertGetId([
                'grupo_id'    => $data['grupo_id'],
                'aula_id'     => $data['aula_id'],
                'dia_semana'  => $data['dia_semana'],
                'hora_inicio' => $data['hora_inicio'],
                'hora_fin'    => $data['hora_fin'],
                'estado'      => 'ACTIVO',
            ], 'id_horario');

            // (opcional) bitácora aquí

            return response()->json(['id_horario' => $id], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            // 23503 = foreign_key_violation
            if ($e->getCode() === '23503') {
                return response()->json([
                    'message' => 'Relación inválida: grupo/aula no existen o no corresponden.',
                    'pgcode'  => $e->getCode()
                ], 422);
            }
            throw $e;
        }
    }

    public function update($id, Request $req)
    {
        $data = $req->validate([
            'aula_id'    => ['sometimes','integer'],
            'dia_semana' => ['sometimes','integer','between:1,7'],
            'hora_inicio'=> ['sometimes','date_format:H:i'],
            'hora_fin'   => ['sometimes','date_format:H:i','after:hora_inicio'],
            'estado'     => ['sometimes','in:ACTIVO,INACTIVO'],
        ]);

        // Validaciones de existencia si vienen en el payload
        if (isset($data['aula_id'])) {
            $okAula = DB::table('academia.aula')->where('id_aula', $data['aula_id'])->exists();
            if (!$okAula) {
                throw ValidationException::withMessages(['aula_id' => 'El aula no existe.']);
            }
        }

        DB::table('academia.horario')->where('id_horario', $id)->update($data);

        return response()->noContent();
    }

    public function destroy($id)
    {
        DB::table('academia.horario')->where('id_horario', $id)->delete();
        return response()->noContent();
    }
}
