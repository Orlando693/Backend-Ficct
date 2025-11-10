<?php

namespace App\Http\Controllers\Api\Programacion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AutoController extends Controller
{
    public function preview(Request $r) { return $this->run($r, true); }
    public function commit(Request $r)  { return $this->run($r, false); }

    protected function run(Request $r, bool $dry)
    {
        $data = $r->validate([
            'gestion_id'   => 'required|integer',
            'duracion_min' => 'nullable|integer|min:30', // default 90
            'turno'        => 'nullable|in:manana,tarde,noche', // default mañana
            'dias'         => 'nullable|array',  // ej [1,2,3,4,5]
            'dias.*'       => 'integer|min:1|max:7',
        ]);

        $dur = $data['duracion_min'] ?? 90;

        $rang = DB::selectOne("
            SELECT hora_manana_ini,hora_manana_fin,
                   hora_tarde_ini, hora_tarde_fin,
                   hora_noche_ini, hora_noche_fin
            FROM academia.gestion_parametros
            WHERE gestion_id=:gid LIMIT 1",
            ['gid'=>$data['gestion_id']]
        );
        if (!$rang) return response()->json(['message'=>'No hay parámetros de gestión'], 422);

        [$ini,$fin] = match($r->turno) {
          'tarde'  => [$rang->hora_tarde_ini,  $rang->hora_tarde_fin],
          'noche'  => [$rang->hora_noche_ini,  $rang->hora_noche_fin],
          default  => [$rang->hora_manana_ini, $rang->hora_manana_fin],
        };
        $dias = $data['dias'] ?? [1,2,3,4,5];

        // Grupos sin bloques en esa gestión
        $grupos = DB::select("
          SELECT g.id_grupo, g.capacidad
          FROM academia.grupo g
          WHERE g.gestion_id=:gid
            AND NOT EXISTS(SELECT 1 FROM academia.horario h WHERE h.grupo_id=g.id_grupo)
          ORDER BY g.id_grupo",
          ['gid'=>$data['gestion_id']]
        );

        $propuestas = [];
        $pendientes = [];

        foreach ($grupos as $g) {
            $aulas = DB::select("
              SELECT a.id_aula, a.capacidad
              FROM academia.aula a
              WHERE a.estado='ACTIVA' AND a.capacidad >= :cap
              ORDER BY a.capacidad",
              ['cap'=>$g->capacidad]
            );

            $asignado = false;
            foreach ($dias as $d) {
                $cursor = new \DateTime($ini);
                $end    = new \DateTime($fin);

                while ($cursor < $end) {
                    $t0 = (clone $cursor);
                    $t1 = (clone $cursor)->modify("+{$dur} minutes");
                    if ($t1 > $end) break;

                    foreach ($aulas as $a) {
                        $busyAula = DB::selectOne("
                          SELECT 1
                          FROM academia.horario h
                          JOIN academia.grupo gg ON gg.id_grupo=h.grupo_id
                          WHERE gg.gestion_id=:gid AND h.aula_id=:aula AND h.dia_semana=:dia
                            AND (:t0 < h.hora_fin) AND (:t1 > h.hora_inicio)
                          LIMIT 1",
                          ['gid'=>$data['gestion_id'],'aula'=>$a->id_aula,'dia'=>$d,
                           't0'=>$t0->format('H:i'),'t1'=>$t1->format('H:i')]
                        );

                        $busyGrupo = DB::selectOne("
                          SELECT 1
                          FROM academia.horario h
                          WHERE h.grupo_id=:g AND h.dia_semana=:dia
                            AND (:t0 < h.hora_fin) AND (:t1 > h.hora_inicio)
                          LIMIT 1",
                          ['g'=>$g->id_grupo,'dia'=>$d,
                           't0'=>$t0->format('H:i'),'t1'=>$t1->format('H:i')]
                        );

                        if (!$busyAula && !$busyGrupo) {
                            $p = [
                              'grupo_id'   => $g->id_grupo,
                              'aula_id'    => $a->id_aula,
                              'dia_semana' => $d,
                              'hora_inicio'=> $t0->format('H:i'),
                              'hora_fin'   => $t1->format('H:i'),
                            ];
                            $propuestas[] = $p;

                            if (!$dry) {
                                DB::insert("
                                  INSERT INTO academia.horario(grupo_id,aula_id,dia_semana,hora_inicio,hora_fin)
                                  VALUES (:g,:aula,:dia,:t0,:t1)",
                                  ['g'=>$g->id_grupo,'aula'=>$a->id_aula,'dia'=>$d,
                                   't0'=>$p['hora_inicio'],'t1'=>$p['hora_fin']]
                                );
                            }
                            $asignado = true;
                            break 3; // listo este grupo
                        }
                    }
                    $cursor->modify('+30 minutes');
                }
            }

            if (!$asignado) {
                $pendientes[] = ['grupo_id'=>$g->id_grupo,'motivo'=>'Sin aula/slot disponible'];
            }
        }

        // Bitácora
        DB::select("SELECT academia.fn_log_bitacora(?, ?, ?, ?, ?, ?, ?, ?)", [
            optional($r->user())->id_persona, 'Jefatura',
            'Programación', $dry ? 'AUTO_PREVIEW' : 'AUTO_COMMIT',
            'Generación','OK', null,
            json_encode(['gestion_id'=>$data['gestion_id'],'duracion_min'=>$dur,'turno'=>$r->turno ?? 'manana'])
        ]);

        return response()->json([
            'dry_run'      => $dry,
            'gestion_id'   => (int)$data['gestion_id'],
            'duracion_min' => $dur,
            'turno'        => $r->turno ?? 'manana',
            'propuestas'   => $propuestas,
            'pendientes'   => $pendientes,
            'creados'      => $dry ? 0 : count($propuestas),
        ]);
    }
}
