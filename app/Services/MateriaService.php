<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class MateriaService
{
    // -------- Listado con filtros (usa vw_materia_resumen) --------
    public function list(array $filters): array
    {
        $q      = $filters['q']      ?? null;
        $estado = $filters['estado'] ?? null;
        $page   = max(1, (int)($filters['page'] ?? 1));
        $per    = max(1, min(100, (int)($filters['per_page'] ?? 10)));

        $base = DB::table(DB::raw('academia.vw_materia_resumen as m'));

        if ($estado) {
            $base->where('m.estado', $estado);
        }
        if ($q) {
            $base->where(function ($w) use ($q) {
                $w->whereRaw('m.codigo ILIKE ?', ["%{$q}%"])
                  ->orWhereRaw('m.nombre ILIKE ?', ["%{$q}%"]);
            });
        }

        $total = (clone $base)->count('*');
        $rows  = $base->orderBy('m.codigo')
                      ->offset(($page - 1) * $per)
                      ->limit($per)
                      ->get();

        return [
            'data' => $rows,
            'meta' => ['page' => $page, 'per_page' => $per, 'total' => $total],
        ];
    }

    // -------- Mini lista para combos --------
    public function mini(): array
    {
        $rows = DB::table(DB::raw('academia.materia'))
            ->select('id_materia','codigo','nombre','creditos','estado')
            ->orderBy('codigo')
            ->get();

        return ['data' => $rows];
    }

    // -------- Crear --------
    public function create(array $input): array
    {
        try {
            $row = DB::transaction(function () use ($input) {
                $row = DB::selectOne(
                    'INSERT INTO academia.materia (codigo,nombre,creditos)
                     VALUES (UPPER(TRIM(?)), TRIM(?), ?) RETURNING id_materia',
                    [$input['codigo'], $input['nombre'], $input['creditos']]
                );

                // bitácora (si tienes la función):
                @DB::select('SELECT academia.fn_log_bitacora(?, ?, ?, ?, ?, ?, ?, ?)', [
                    null, 'Jefatura', 'Materias', 'crear', 'Materia:'.$input['codigo'], 'OK', null, null
                ]);

                return $this->findResumen($row->id_materia);
            });
            return ['data' => $row];
        } catch (QueryException $e) {
            // 23505 = unique_violation
            if ($e->getCode() === '23505') {
                abort(422, 'Código duplicado.');
            }
            throw $e;
        }
    }

    // -------- Actualizar --------
    public function update(int $id, array $input): array
    {
        try {
            $row = DB::transaction(function () use ($id, $input) {
                $count = DB::update(
                    'UPDATE academia.materia
                       SET codigo=UPPER(TRIM(?)), nombre=TRIM(?), creditos=?
                     WHERE id_materia=?',
                    [$input['codigo'], $input['nombre'], $input['creditos'], $id]
                );
                if ($count === 0) abort(404, 'Materia no encontrada.');

                @DB::select('SELECT academia.fn_log_bitacora(?, ?, ?, ?, ?, ?, ?, ?)', [
                    null, 'Jefatura', 'Materias', 'editar', 'Materia:'.$input['codigo'], 'OK', null, null
                ]);

                return $this->findResumen($id);
            });
            return ['data' => $row];
        } catch (QueryException $e) {
            if ($e->getCode() === '23505') {
                abort(422, 'Código duplicado.');
            }
            throw $e;
        }
    }

    // -------- Cambiar estado con validación de "programación vigente" --------
    public function setEstado(int $id, string $estado): array
    {
        return DB::transaction(function () use ($id, $estado) {
            // recuperar código para mensaje/bitácora
            $m = DB::selectOne('SELECT codigo FROM academia.materia WHERE id_materia=?', [$id]);
            if (!$m) abort(404, 'Materia no encontrada.');

            // si intenta INACTIVA, valida que no tenga grupos vigentes
            if ($estado === 'INACTIVA') {
                $cnt = DB::selectOne(
                    "SELECT COUNT(*) AS c
                       FROM academia.grupo g
                       JOIN academia.materia_carrera mc ON mc.id_materia_carrera=g.materia_carrera_id
                       JOIN academia.gestion_academica ga ON ga.id_gestion=g.gestion_id
                      WHERE mc.materia_id=? AND ga.fecha_fin >= CURRENT_DATE",
                    [$id]
                );
                if (($cnt->c ?? 0) > 0) {
                    abort(422, "No se puede inactivar: programación vigente ({$cnt->c}).");
                }
            }

            DB::update('UPDATE academia.materia SET estado=? WHERE id_materia=?', [$estado, $id]);

            @DB::select('SELECT academia.fn_log_bitacora(?, ?, ?, ?, ?, ?, ?, ?)', [
                null, 'Jefatura', 'Materias', 'set_estado', 'Materia:'.$m->codigo, 'OK', json_encode($estado), null
            ]);

            return ['data' => $this->findResumen($id)];
        });
    }

    // -------- Helper: trae fila desde la vista de resumen --------
    private function findResumen(int $id)
    {
        return DB::table(DB::raw('academia.vw_materia_resumen'))
                 ->where('id_materia', $id)
                 ->first();
    }
}
