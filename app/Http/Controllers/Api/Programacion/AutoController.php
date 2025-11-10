<?php

namespace App\Http\Controllers\Api\Programacion;

use App\Http\Controllers\Controller;
use App\Support\Bitacora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AutoController extends Controller
{
    public function preview(Request $request)
    {
        $result = $this->generate($request, true);

        return response()->json([
            'data' => [
                'rows' => $result['rows'],
                'totals' => $result['totals'],
            ],
        ]);
    }

    public function confirm(Request $request)
    {
        $result = DB::transaction(fn () => $this->generate($request, false));

        return response()->json([
            'data' => [
                'inserted' => $result['inserted'],
                'updated'  => 0,
                'skipped'  => $result['skipped'],
                'errors'   => $result['errors'],
            ],
        ]);
    }

    private function generate(Request $request, bool $dryRun): array
    {
        $data = $request->validate([
            'gestion_id'   => ['required','integer','min:1'],
            'duracion_min' => ['nullable','integer','min:30','max:240'],
            'turno'        => ['nullable','in:manana,tarde,noche'],
            'dias'         => ['nullable','array'],
            'dias.*'       => ['integer','between:1,7'],
        ]);

        $gestionId = (int) $data['gestion_id'];
        $duracion  = $data['duracion_min'] ?? 90;
        $turno     = $data['turno'] ?? 'manana';
        $dias      = $data['dias'] ?: [1,2,3,4,5];

        $turnosCfg = $this->currentTurnos();
        $turnoCfg  = collect($turnosCfg)->firstWhere('turno', $turno) ?? $turnosCfg[0];
        $horaIni   = $turnoCfg['inicio'].':00';
        $horaFin   = $turnoCfg['fin'].':00';

        $groups = DB::table('academia.vw_grupo_resumen')
            ->select('id_grupo','capacidad','materia_label','paralelo','turno')
            ->where('gestion_id', $gestionId)
            ->orderBy('materia_label')
            ->get();

        $rows = [];
        $inserted = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($groups as $group) {
            $label = trim(($group->materia_label ?? 'Grupo '.$group->id_grupo).' · '.$group->paralelo);
            $suggestion = $this->findSlot($gestionId, $group, $dias, $horaIni, $horaFin, $duracion);

            if ($suggestion) {
                $rows[] = [
                    'grupo_id'    => (int) $group->id_grupo,
                    'grupo_label' => $label,
                    'sugerido'    => $suggestion,
                    'status'      => 'ok',
                    'detalle'     => null,
                ];

                if (!$dryRun) {
                    DB::insert(
                        "INSERT INTO academia.horario(grupo_id,aula_id,dia_semana,hora_inicio,hora_fin)
                         VALUES (?,?,?,?,?)",
                        [
                            $group->id_grupo,
                            $suggestion['aula_id'],
                            $suggestion['dia_semana'],
                            $suggestion['hora_inicio'],
                            $suggestion['hora_fin'],
                        ]
                    );
                    $inserted++;
                }
            } else {
                $rows[] = [
                    'grupo_id'    => (int) $group->id_grupo,
                    'grupo_label' => $label,
                    'sugerido'    => null,
                    'status'      => 'pendiente',
                    'detalle'     => 'Sin aula disponible en el rango solicitado',
                ];
                $skipped++;
            }
        }

        $totals = [
            'total'      => count($rows),
            'ok'         => count(array_filter($rows, fn($r) => $r['status'] === 'ok')),
            'pendientes' => count(array_filter($rows, fn($r) => $r['status'] === 'pendiente')),
            'conflictos' => count(array_filter($rows, fn($r) => $r['status'] === 'conflicto')),
        ];

        Bitacora::log(
            optional($request->user())->id_persona,
            'Jefatura',
            'Programacion',
            $dryRun ? 'auto_preview' : 'auto_confirm',
            'AutoProgramacion',
            'OK',
            null,
            [
                'gestion_id' => $gestionId,
                'duracion_min' => $duracion,
                'turno' => $turno,
            ]
        );

        return [
            'rows' => $rows,
            'totals' => $totals,
            'inserted' => $inserted,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    private function findSlot(int $gestionId, $group, array $dias, string $horaIni, string $horaFin, int $duracion): ?array
    {
        $aulas = DB::select("
            SELECT a.id_aula, a.capacidad, a.codigo
            FROM academia.aula a
            WHERE a.estado='ACTIVA' AND a.capacidad >= :cap
            ORDER BY a.capacidad ASC
        ", ['cap' => $group->capacidad]);

        foreach ($dias as $dia) {
            $cursor = new \DateTime($horaIni);
            $end    = new \DateTime($horaFin);

            while ($cursor < $end) {
                $slotEnd = (clone $cursor)->modify("+{$duracion} minutes");
                if ($slotEnd > $end) {
                    break;
                }

                $slotIniText = $cursor->format('H:i:s');
                $slotFinText = $slotEnd->format('H:i:s');

                foreach ($aulas as $aula) {
                    $busyAula = DB::selectOne("
                        SELECT 1
                        FROM academia.horario h
                        JOIN academia.grupo g ON g.id_grupo = h.grupo_id
                        WHERE g.gestion_id = :gestion
                          AND h.aula_id = :aula
                          AND h.dia_semana = :dia
                          AND (:ini < h.hora_fin) AND (:fin > h.hora_inicio)
                        LIMIT 1
                    ", [
                        'gestion' => $gestionId,
                        'aula' => $aula->id_aula,
                        'dia' => $dia,
                        'ini' => $slotIniText,
                        'fin' => $slotFinText,
                    ]);

                    $busyGrupo = DB::selectOne("
                        SELECT 1
                        FROM academia.horario
                        WHERE grupo_id = :grupo AND dia_semana = :dia
                          AND (:ini < hora_fin) AND (:fin > hora_inicio)
                        LIMIT 1
                    ", [
                        'grupo' => $group->id_grupo,
                        'dia' => $dia,
                        'ini' => $slotIniText,
                        'fin' => $slotFinText,
                    ]);

                    if (!$busyAula && !$busyGrupo) {
                        return [
                            'aula_id'    => (int) $aula->id_aula,
                            'aula_label' => $aula->codigo,
                            'dia_semana' => (int) $dia,
                            'hora_inicio'=> substr($slotIniText, 0, 5),
                            'hora_fin'   => substr($slotFinText, 0, 5),
                        ];
                    }
                }
                $cursor->modify('+30 minutes');
            }
        }

        return null;
    }

    private function currentTurnos(): array
    {
        $row = DB::selectOne("SELECT turnos FROM academia.vw_parametros_actuales");
        if ($row && $row->turnos) {
            $decoded = json_decode($row->turnos, true);
            if (is_array($decoded) && count($decoded)) {
                return $decoded;
            }
        }

        return [
            ['turno'=>'manana','inicio'=>'07:00','fin'=>'11:30'],
            ['turno'=>'tarde','inicio'=>'13:30','fin'=>'17:30'],
            ['turno'=>'noche','inicio'=>'18:45','fin'=>'22:15'],
        ];
    }
}
