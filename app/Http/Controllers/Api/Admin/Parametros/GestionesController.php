<?php

namespace App\Http\Controllers\Api\Admin\Parametros;

use App\Http\Controllers\Controller;
use App\Support\Bitacora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GestionesController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $query = DB::table('academia.gestion_academica');
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->whereRaw('anio::text ILIKE ?', ["%{$q}%"])
                  ->orWhereRaw('periodo::text ILIKE ?', ["%{$q}%"]);
            });
        }

        $rows = $query->orderByDesc('anio')
            ->orderBy('periodo')
            ->get()
            ->map(fn ($r) => $this->format($r));

        return response()->json(['data' => $rows]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'anio'      => 'required|integer|min:2000|max:2100',
            'periodo'   => 'required|integer|min:1|max:3',
            'fecha_ini' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_ini',
            'estado'    => 'nullable|string|max:12',
        ]);

        $row = DB::selectOne(
            "INSERT INTO academia.gestion_academica(anio, periodo, fecha_ini, fecha_fin, estado)
             VALUES(?, ?, ?, ?, COALESCE(?, 'ACTIVO'))
             RETURNING id_gestion",
            [
                $data['anio'],
                $data['periodo'],
                $data['fecha_ini'] ?? null,
                $data['fecha_fin'] ?? null,
                $data['estado'] ?? null,
            ]
        );

        $record = $this->findById($row->id_gestion ?? null);
        Bitacora::log(optional(auth()->user())->id_persona, 'CPD', 'Gestiones', 'crear', 'Gestion:'.$row->id_gestion);

        return response()->json(['data' => $record], 201);
    }

    public function update($id, Request $request)
    {
        $data = $request->validate([
            'anio'      => 'sometimes|integer|min:2000|max:2100',
            'periodo'   => 'sometimes|integer|min:1|max:3',
            'fecha_ini' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_ini',
            'estado'    => 'nullable|string|max:12',
        ]);

        $sets = [];
        $vals = [];
        foreach (['anio','periodo','fecha_ini','fecha_fin','estado'] as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = "$field = ?";
                $vals[] = $data[$field];
            }
        }

        if ($sets) {
            $vals[] = $id;
            DB::update("UPDATE academia.gestion_academica SET ".implode(', ', $sets)." WHERE id_gestion = ?", $vals);
            Bitacora::log(optional(auth()->user())->id_persona, 'CPD', 'Gestiones', 'editar', 'Gestion:'.$id);
        }

        return response()->json(['data' => $this->findById($id)]);
    }

    public function destroy($id)
    {
        DB::delete('DELETE FROM academia.gestion_academica WHERE id_gestion = ?', [$id]);
        Bitacora::log(optional(auth()->user())->id_persona, 'CPD', 'Gestiones', 'eliminar', 'Gestion:'.$id);
        return response()->json(['message' => 'Gestión eliminada']);
    }

    private function findById($id): ?array
    {
        if (!$id) {
            return null;
        }
        $row = DB::selectOne('SELECT * FROM academia.gestion_academica WHERE id_gestion = ?', [$id]);
        return $row ? $this->format($row) : null;
    }

    private function format($row): array
    {
        $fechaIni = $row->fecha_ini ?? $row->fecha_inicio ?? null;
        $fechaFin = $row->fecha_fin ?? $row->fecha_termino ?? null;

        return [
            'id_gestion' => (int) $row->id_gestion,
            'anio' => (int) $row->anio,
            'periodo' => (int) $row->periodo,
            'fecha_ini' => $fechaIni,
            'fecha_fin' => $fechaFin,
            'label' => $row->anio.'-'.$row->periodo,
        ];
    }
}
