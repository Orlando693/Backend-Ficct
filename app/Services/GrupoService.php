<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GrupoService
{
    public function list(array $q): array
    {
        $page = max(1, (int)($q['page'] ?? 1));
        $limit = max(1, min(100, (int)($q['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $gestionId  = $q['gestion_id'] ?? null;
        $materiaId  = $q['materia_id'] ?? null;
        $estado     = $q['estado'] ?? null;
        $search     = trim((string)($q['search'] ?? ''));

        $where = [];
        $params = [];

        if (is_numeric($gestionId)) { $where[] = 'g.gestion_id = ?'; $params[] = (int)$gestionId; }
        if (is_numeric($materiaId)) { $where[] = 'g.materia_id = ?'; $params[] = (int)$materiaId; }
        if (in_array($estado, ['ACTIVO','INACTIVO'])) { $where[] = 'g.estado = ?'; $params[] = $estado; }
        if ($search !== '') {
            $where[] = '(g.paralelo ILIKE ? OR materia_codigo ILIKE ? OR materia_nombre ILIKE ?)';
            $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
        }

        $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

        $total = DB::table(DB::raw('academia.vw_grupo_resumen as g'))
                    ->whereRaw($where ? implode(' AND ', array_map(fn($x)=>$x, $where)) : 'true', $params)
                    ->count('*');

        $rows = DB::select("
            SELECT g.* 
            FROM academia.vw_grupo_resumen g
            $whereSql
            ORDER BY materia_codigo, paralelo
            LIMIT $limit OFFSET $offset
        ", $params);

        return [
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
            'data'  => $rows,
        ];
    }

    public function mini(?int $gestionId): array
    {
        $params = [];
        $where  = '';
        if ($gestionId) { $where = 'WHERE g.gestion_id = ?'; $params[] = $gestionId; }

        return DB::select("
            SELECT
              g.id_grupo,
              g.paralelo,
              g.turno,
              g.capacidad,
              g.materia_id,
              g.materia_label
            FROM academia.vw_grupo_resumen g
            $where
            ORDER BY materia_label, paralelo
        ", $params);
    }

    public function create(array $in): array
    {
        $personaId = optional(Auth::user())->id_persona ?? null;

        // Duplicidad (gestion+materia+paralelo)
        $dup = DB::selectOne("
            SELECT 1
            FROM academia.grupo
            WHERE gestion_id = ? AND materia_id = ?
              AND UPPER(TRIM(paralelo)) = UPPER(TRIM(?))
            LIMIT 1
        ", [ $in['gestion_id'], $in['materia_id'], $in['paralelo'] ]);

        if ($dup) {
            throw new \RuntimeException('Ya existe un grupo con ese paralelo para esa materia en la gestión.');
        }

        return DB::transaction(function() use ($in, $personaId) {

            $row = DB::selectOne("
                INSERT INTO academia.grupo(gestion_id, materia_id, paralelo, turno, capacidad)
                VALUES (?,?,?,?,?)
                RETURNING id_grupo
            ", [ $in['gestion_id'], $in['materia_id'], $in['paralelo'], $in['turno'], $in['capacidad'] ]);

            $id = (int)$row->id_grupo;

            // Bitácora
            DB::select("
                SELECT academia.fn_log_bitacora(?, 'Jefatura', 'Grupos', 'crear', ?, 'OK', ?, ?)
            ", [
                $personaId,
                "Grupo:$id",
                'Crear grupo',
                json_encode($in, JSON_UNESCAPED_UNICODE),
            ]);

            return [ 'id_grupo' => $id ];
        });
    }

    public function update(int $id, array $in): array
    {
        $personaId = optional(Auth::user())->id_persona ?? null;

        if (isset($in['paralelo'])) {
            $dup = DB::selectOne("
                SELECT 1
                FROM academia.grupo
                WHERE UPPER(TRIM(paralelo)) = UPPER(TRIM(?))
                  AND id_grupo <> ?
            ", [ $in['paralelo'], $id ]);
            if ($dup) {
                throw new \RuntimeException('Paralelo duplicado para otro grupo.');
            }
        }

        $sets = [];
        $vals = [];
        foreach (['paralelo','turno','capacidad','estado'] as $k) {
            if (array_key_exists($k, $in)) { $sets[] = "$k = ?"; $vals[] = $in[$k]; }
        }
        if (!$sets) return ['updated'=>0];

        $vals[] = $id;

        return DB::transaction(function() use ($sets, $vals, $id, $personaId, $in) {
            DB::update("UPDATE academia.grupo SET ".implode(', ', $sets)." WHERE id_grupo = ?", $vals);

            DB::select("
                SELECT academia.fn_log_bitacora(?, 'Jefatura', 'Grupos', 'editar', ?, 'OK', ?, ?)
            ", [
                $personaId,
                "Grupo:$id",
                'Editar grupo',
                json_encode($in, JSON_UNESCAPED_UNICODE),
            ]);

            return ['updated'=>1];
        });
    }

    public function toggleEstado(int $id): array
    {
        $personaId = optional(Auth::user())->id_persona ?? null;

        $curr = DB::selectOne("SELECT estado FROM academia.grupo WHERE id_grupo = ?", [$id]);
        if (!$curr) throw new \RuntimeException('Grupo no encontrado.');

        $to = $curr->estado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';

        // Regla: si voy a INACTIVO, validar que no tenga programación vigente
        if ($to === 'INACTIVO') {
            $check = DB::selectOne("
                SELECT CASE
                   WHEN to_regclass('academia.horario') IS NULL THEN false
                   ELSE EXISTS(SELECT 1 FROM academia.horario WHERE grupo_id = ?)
                END AS hay
            ", [$id]);
            if ($check && ($check->hay === true || $check->hay === 't')) {
                throw new \RuntimeException('Rechazado: el grupo tiene horarios programados.');
            }
        }

        DB::transaction(function() use ($id, $to, $personaId) {
            DB::update("UPDATE academia.grupo SET estado = ? WHERE id_grupo = ?", [$to, $id]);
            DB::select("
                SELECT academia.fn_log_bitacora(?, 'Jefatura', 'Grupos', 'estado', ?, 'OK', ?, ?)
            ", [
                $personaId,
                "Grupo:$id",
                "Cambiar estado a $to",
                json_encode(['id_grupo'=>$id,'estado'=>$to], JSON_UNESCAPED_UNICODE),
            ]);
        });

        return ['estado'=>$to];
    }
}
