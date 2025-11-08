<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GrupoService
{
    public function list(array $q): array
    {
        $page = max(1, (int)($q['page'] ?? 1));
        $per  = max(1, min(100, (int)($q['limit'] ?? $q['per_page'] ?? 20)));

        $query = DB::table(DB::raw('academia.vw_grupo_resumen as g'));

        if (is_numeric($q['gestion_id'] ?? null)) {
            $query->where('g.gestion_id', (int) $q['gestion_id']);
        }
        if (is_numeric($q['materia_id'] ?? null)) {
            $query->where('g.materia_id', (int) $q['materia_id']);
        }
        if (in_array($q['estado'] ?? null, ['ACTIVO', 'INACTIVO'], true)) {
            $query->where('g.estado', $q['estado']);
        }

        $search = trim((string)($q['search'] ?? $q['q'] ?? ''));
        if ($search !== '') {
            $query->where(function ($w) use ($search) {
                $w->where('g.paralelo', 'ILIKE', "%{$search}%")
                  ->orWhere('g.materia_codigo', 'ILIKE', "%{$search}%")
                  ->orWhere('g.materia_nombre', 'ILIKE', "%{$search}%");
            });
        }

        $total = (clone $query)->count();

        $rows = $query->orderByDesc('g.gestion_id')
            ->orderBy('g.materia_codigo')
            ->orderBy('g.paralelo')
            ->offset(($page - 1) * $per)
            ->limit($per)
            ->get();

        return [
            'data' => $rows,
            'meta' => [
                'page' => $page,
                'per_page' => $per,
                'total' => $total,
            ],
        ];
    }

    public function mini(?int $gestionId): array
    {
        $query = DB::table(DB::raw('academia.vw_grupo_resumen as g'))
            ->select(
                'g.id_grupo',
                'g.gestion_id',
                'g.anio',
                'g.periodo',
                'g.paralelo',
                'g.turno',
                'g.capacidad',
                'g.materia_id',
                'g.materia_label'
            );

        if ($gestionId) {
            $query->where('g.gestion_id', $gestionId);
        }

        return $query->orderBy('g.materia_label')
            ->orderBy('g.paralelo')
            ->get()
            ->toArray();
    }

    public function create(array $in): array
    {
        $personaId = optional(Auth::user())->id_persona ?? null;

        $dup = DB::selectOne(
            "SELECT 1 FROM academia.grupo
             WHERE gestion_id = ? AND materia_id = ?
               AND UPPER(TRIM(paralelo)) = UPPER(TRIM(?))
             LIMIT 1",
            [$in['gestion_id'], $in['materia_id'], $in['paralelo']]
        );

        if ($dup) {
            abort(422, 'Ya existe un grupo con ese paralelo en la gestion seleccionada.');
        }

        return DB::transaction(function () use ($in, $personaId) {
            $row = DB::selectOne(
                "INSERT INTO academia.grupo(gestion_id, materia_id, paralelo, turno, capacidad)
                 VALUES (?,?,?,?,?) RETURNING id_grupo",
                [$in['gestion_id'], $in['materia_id'], $in['paralelo'], $in['turno'], $in['capacidad']]
            );

            $id = (int) $row->id_grupo;

            DB::select(
                "SELECT academia.fn_log_bitacora(?, 'Jefatura', 'Grupos', 'crear', ?, 'OK', ?, ?)",
                [
                    $personaId,
                    "Grupo:$id",
                    'Crear grupo',
                    json_encode($in, JSON_UNESCAPED_UNICODE),
                ]
            );

            return ['data' => $this->findResumen($id)];
        });
    }

    public function update(int $id, array $in): array
    {
        $personaId = optional(Auth::user())->id_persona ?? null;

        $curr = DB::selectOne("SELECT gestion_id, materia_id, paralelo FROM academia.grupo WHERE id_grupo = ?", [$id]);
        if (!$curr) {
            abort(404, 'Grupo no encontrado.');
        }

        $targetGestion = (int) ($in['gestion_id'] ?? $curr->gestion_id);
        $targetMateria = (int) ($in['materia_id'] ?? $curr->materia_id);
        $targetParalelo = $in['paralelo'] ?? $curr->paralelo;

        $dup = DB::selectOne(
            "SELECT 1 FROM academia.grupo
             WHERE gestion_id = ? AND materia_id = ?
               AND UPPER(TRIM(paralelo)) = UPPER(TRIM(?))
               AND id_grupo <> ?",
            [$targetGestion, $targetMateria, $targetParalelo, $id]
        );
        if ($dup) {
            abort(422, 'Ya existe otro grupo con esos datos.');
        }

        $allowed = ['gestion_id', 'materia_id', 'paralelo', 'turno', 'capacidad', 'estado'];
        $sets = [];
        $vals = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $in)) {
                $sets[] = "{$field} = ?";
                $vals[] = $in[$field];
            }
        }

        if (!$sets) {
            return ['data' => $this->findResumen($id)];
        }

        $vals[] = $id;

        return DB::transaction(function () use ($sets, $vals, $id, $personaId, $in) {
            $updated = DB::update(
                "UPDATE academia.grupo SET " . implode(', ', $sets) . " WHERE id_grupo = ?",
                $vals
            );

            DB::select(
                "SELECT academia.fn_log_bitacora(?, 'Jefatura', 'Grupos', 'editar', ?, 'OK', ?, ?)",
                [
                    $personaId,
                    "Grupo:$id",
                    'Editar grupo',
                    json_encode($in, JSON_UNESCAPED_UNICODE),
                ]
            );

            return ['data' => $this->findResumen($id)];
        });
    }

    public function toggleEstado(int $id): array
    {
        $personaId = optional(Auth::user())->id_persona ?? null;

        $curr = DB::selectOne("SELECT estado FROM academia.grupo WHERE id_grupo = ?", [$id]);
        if (!$curr) {
            abort(404, 'Grupo no encontrado.');
        }

        $to = $curr->estado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';

        if ($to === 'INACTIVO') {
            $check = DB::selectOne(
                "SELECT CASE
                    WHEN to_regclass('academia.horario') IS NULL THEN false
                    ELSE EXISTS(SELECT 1 FROM academia.horario WHERE grupo_id = ?)
                END AS hay",
                [$id]
            );
            if ($check && ($check->hay === true || $check->hay === 't')) {
                abort(422, 'Rechazado: el grupo tiene horarios programados.');
            }
        }

        DB::transaction(function () use ($id, $to, $personaId) {
            DB::update("UPDATE academia.grupo SET estado = ? WHERE id_grupo = ?", [$to, $id]);
            DB::select(
                "SELECT academia.fn_log_bitacora(?, 'Jefatura', 'Grupos', 'estado', ?, 'OK', ?, ?)",
                [
                    $personaId,
                    "Grupo:$id",
                    "Cambiar estado a $to",
                    json_encode(['id_grupo' => $id, 'estado' => $to], JSON_UNESCAPED_UNICODE),
                ]
            );
        });

        return ['data' => $this->findResumen($id)];
    }

    public function delete(int $id): array
    {
        $personaId = optional(Auth::user())->id_persona ?? null;

        return DB::transaction(function () use ($id, $personaId) {
            $row = DB::selectOne(
                "SELECT materia_id, gestion_id, paralelo
                 FROM academia.grupo WHERE id_grupo = ?",
                [$id]
            );
            if (!$row) {
                abort(404, 'Grupo no encontrado.');
            }

            $blocked = DB::selectOne(
                "SELECT CASE
                    WHEN to_regclass('academia.horario') IS NULL THEN false
                    ELSE EXISTS(SELECT 1 FROM academia.horario WHERE grupo_id = ?)
                END AS hay",
                [$id]
            );
            if ($blocked && ($blocked->hay === true || $blocked->hay === 't')) {
                abort(422, 'No se puede eliminar: tiene horarios asociados.');
            }

            DB::delete("DELETE FROM academia.grupo WHERE id_grupo = ?", [$id]);

            DB::select(
                "SELECT academia.fn_log_bitacora(?, 'Jefatura', 'Grupos', 'eliminar', ?, 'OK', ?, ?)",
                [
                    $personaId,
                    "Grupo:$id",
                    'Eliminar grupo',
                    json_encode(['id_grupo' => $id], JSON_UNESCAPED_UNICODE),
                ]
            );

            return ['message' => 'Grupo eliminado.'];
        });
    }

    private function findResumen(int $id)
    {
        return DB::table(DB::raw('academia.vw_grupo_resumen'))
            ->where('id_grupo', $id)
            ->first();
    }
}
