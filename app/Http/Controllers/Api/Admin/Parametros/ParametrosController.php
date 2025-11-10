<?php

namespace App\Http\Controllers\Api\Admin\Parametros;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parametros\UpdateParametrosRequest;
use App\Support\Bitacora;
use Illuminate\Support\Facades\DB;

class ParametrosController extends Controller
{
    public function show()
    {
        return response()->json(['data' => $this->currentParametros()]);
    }

    public function update(UpdateParametrosRequest $req)
    {
        $data = $req->validated();

        $slots = $data['turnos'];
        usort($slots, fn ($a, $b) => strcmp($a['inicio'], $b['inicio']));
        for ($i = 0; $i < count($slots); $i++) {
            if ($slots[$i]['inicio'] >= $slots[$i]['fin']) {
                return response()->json(['message' => "Turno '{$slots[$i]['turno']}' tiene inicio >= fin"], 422);
            }
            if ($i > 0 && $slots[$i]['inicio'] < $slots[$i - 1]['fin']) {
                return response()->json(['message' => "Solapamiento entre turnos '{$slots[$i - 1]['turno']}' y '{$slots[$i]['turno']}'"], 422);
            }
        }

        DB::transaction(function () use ($data) {
            DB::table('academia.parametros_vigencia')->insert([
                'vigente_desde'       => now()->toDateString(),
                'vigente_hasta'       => null,
                'duracion_bloque_min' => $data['duracion_bloque_min'],
                'dias_habiles'        => '{' . implode(',', $data['dias_habiles']) . '}',
                'turnos'              => json_encode($data['turnos']),
            ]);
        });

        Bitacora::log(optional(auth()->user())->id_persona, 'CPD', 'Parametros', 'actualizar', 'Parametros', 'OK', 'Actualizó parámetros', $data);

        return response()->json(['data' => $this->currentParametros()]);
    }

    private function currentParametros(): array
    {
        $row = DB::selectOne("SELECT * FROM academia.vw_parametros_actuales");
        if (!$row) {
            return [
                'duracion_bloque_min' => 60,
                'dias_habiles' => [1, 2, 3, 4, 5],
                'turnos' => [
                    ['turno' => 'manana', 'inicio' => '07:00', 'fin' => '11:30'],
                    ['turno' => 'tarde',  'inicio' => '13:30', 'fin' => '17:30'],
                    ['turno' => 'noche',  'inicio' => '18:45', 'fin' => '22:15'],
                ],
            ];
        }

        $dias = $row->dias_habiles ?? [];
        if (is_string($dias)) {
            $dias = array_filter(explode(',', trim($dias, '{}')));
        }

        $turnos = is_string($row->turnos) ? json_decode($row->turnos, true) : ($row->turnos ?? []);

        return [
            'duracion_bloque_min' => (int) $row->duracion_bloque_min,
            'dias_habiles' => array_map('intval', $dias),
            'turnos' => $turnos ?: [],
        ];
    }
}
