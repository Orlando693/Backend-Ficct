<?php

namespace App\Http\Controllers\Api\AsistenciaDocente;

use App\Http\Controllers\Controller;
use App\Http\Requests\AsistenciaDocente\StoreAsistenciaRequest;
use App\Http\Requests\AsistenciaDocente\UpdateAsistenciaRequest;
use App\Http\Resources\AsistenciaDocente\AsistenciaResource;
use App\Models\AsistenciaDocente;
use App\Traits\LogsToBitacora;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AsistenciaController extends Controller
{
    use LogsToBitacora;

    private function canManage(Request $request): bool
    {
        $u = $request->user();
        if (!$u) {
            return true;
        }
        $role = strtoupper((string) ($u->rol ?? $u->role ?? ''));

        if (in_array($role, ['ADMIN','CPD','DECANATO'])) {
            return true;
        }

        try {
            $prole = strtoupper((string) ($u->persona->rol ?? $u->persona->role ?? ''));
            if (in_array($prole, ['ADMIN','CPD','DECANATO'])) {
                return true;
            }
        } catch (\Throwable $e) {}

        if (method_exists($u, 'tokenCan') && (
            $u->tokenCan('*') ||
            $u->tokenCan('ADMIN') ||
            $u->tokenCan('CPD') ||
            $u->tokenCan('DECANATO') ||
            $u->tokenCan('asistencia:manage')
        )) {
            return true;
        }

        return false;
    }

    private function canMarkSelf(Request $request): bool
    {
        $u = $request->user();
        if (!$u) {
            return true;
        }

        $role = strtoupper((string) ($u->rol ?? $u->role ?? ''));
        if ($role === 'DOCENTE') {
            return true;
        }

        try {
            $prole = strtoupper((string) ($u->persona->rol ?? $u->persona->role ?? ''));
            if ($prole === 'DOCENTE') {
                return true;
            }
        } catch (\Throwable $e) {}

        if (method_exists($u, 'tokenCan') && $u->tokenCan('asistencia:mark')) {
            return true;
        }

        return false;
    }

    private function getPersonaId($u): ?int
    {
        if (isset($u->id_persona)) {
            return (int) $u->id_persona;
        }
        try {
            if (isset($u->persona) && isset($u->persona->id_persona)) {
                return (int) $u->persona->id_persona;
            }
        } catch (\Throwable $e) {}
        return null;
    }

    private function resolvePersonaId(Request $request, bool $fallback = true): ?int
    {
        $id = $this->getPersonaId($request->user());
        if ($id) {
            return $id;
        }
        if ($fallback) {
            return (int) ($request->get('docente_id') ?? 1);
        }
        return null;
    }

    private function tableExists(string $table): bool
    {
        $row = DB::selectOne("SELECT to_regclass(?) AS reg", [$table]);
        return $row && $row->reg !== null;
    }

    public function index(Request $request)
    {
        $docenteId = $request->get('docente_id');
        $gestionId = $request->get('gestion_id');
        $desde     = $request->get('desde');
        $hasta     = $request->get('hasta');
        $q         = trim((string) $request->get('q', ''));
        $perPage   = (int) ($request->get('per_page', 30)) ?: 30;

        $canManage = $this->canManage($request);

        if (!$canManage) {
            $docenteId = $this->resolvePersonaId($request);
        }

        $query = AsistenciaDocente::query()
            ->with(['docente','programacion'])
            ->when($docenteId, fn($q2) => $q2->where('docente_id', $docenteId))
            ->when($gestionId, fn($q2) => $q2->where('gestion_id', $gestionId))
            ->when($desde,     fn($q2) => $q2->whereDate('fecha', '>=', $desde))
            ->when($hasta,     fn($q2) => $q2->whereDate('fecha', '<=', $hasta))
            ->when($q !== '', function($qq) use ($q) {
                $qq->where(function($w) use ($q) {
                    $w->where('estado', 'ilike', "%{$q}%")
                      ->orWhere('observacion', 'ilike', "%{$q}%");
                });
            })
            ->orderBy('fecha','desc')->orderBy('hora_ingreso','asc');

        $page = $query->paginate($perPage);

        $this->logBitacora($request, 'Asistencia', 'listar', 'AsistenciaDocente', 'Listado de asistencias');

        return AsistenciaResource::collection($page)->additional([
            'meta' => [
                'total' => $page->total(),
                'per_page' => $page->perPage(),
                'current_page' => $page->currentPage(),
            ]
        ]);
    }

    public function meHoy(Request $request)
    {
        if (!$this->canMarkSelf($request) && !$this->canManage($request)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        $pid = $this->resolvePersonaId($request);

        $rec = AsistenciaDocente::with(['docente','programacion'])
            ->where('docente_id', $pid)
            ->whereDate('fecha', now()->toDateString())
            ->orderByDesc('created_at')
            ->first();

        return $rec
            ? new AsistenciaResource($rec)
            : response()->json(['data' => null]);
    }

    public function store(StoreAsistenciaRequest $request)
    {
        if (!$this->canManage($request)) {
            return response()->json(['message'=>'No autorizado'], 403);
        }

        $data = $request->validated();

        if (empty($data['docente_id'])) {
            $data['docente_id'] = $this->resolvePersonaId($request);
        }

        if (empty($data['docente_id'])) {
            return response()->json(['message'=>'docente_id requerido'], 422);
        }

        $exists = AsistenciaDocente::where('docente_id', $data['docente_id'])
            ->whereDate('fecha', $data['fecha'])
            ->exists();

        if ($exists) {
            return response()->json(['message'=>'Ya existe asistencia para este docente en esa fecha'], 422);
        }

        $this->validateTimes($data);

        $rec = AsistenciaDocente::create($data);

        $this->logBitacora($request, 'Asistencia', 'crear', 'AsistenciaDocente',
            "Docente {$rec->docente_id} {$rec->fecha} {$rec->estado} {$rec->hora_ingreso}");

        return (new AsistenciaResource($rec->load(['docente','programacion'])))
            ->additional(['message' => 'Asistencia registrada']);
    }

    public function update(UpdateAsistenciaRequest $request, int $id)
    {
        if (!$this->canManage($request)) {
            return response()->json(['message'=>'No autorizado'], 403);
        }

        $rec = AsistenciaDocente::findOrFail($id);
        $data = $request->validated();

        if (isset($data['fecha']) || isset($data['docente_id'])) {
            $did = (int)($data['docente_id'] ?? $rec->docente_id);
            $f   = ($data['fecha'] ?? $rec->fecha);
            $dup = AsistenciaDocente::where('docente_id', $did)
                    ->whereDate('fecha', $f)->where('id','<>',$rec->id)->exists();
            if ($dup) {
                throw ValidationException::withMessages([
                    'fecha' => 'Ya existe asistencia para este docente en esa fecha.'
                ]);
            }
        }

        $this->validateTimes(array_merge($rec->toArray(), $data));

        $rec->update($data);

        $this->logBitacora($request, 'Asistencia', 'editar', 'AsistenciaDocente', "Editó asistencia {$rec->id}");

        return (new AsistenciaResource($rec->fresh()->load(['docente','programacion'])))
            ->additional(['message' => 'Asistencia actualizada']);
    }

    public function setSalida(Request $request, int $id)
    {
        $rec = AsistenciaDocente::findOrFail($id);

        $pid = $this->resolvePersonaId($request);

        if ($rec->docente_id !== $pid && !$this->canManage($request)) {
            return response()->json(['message'=>'No autorizado'], 403);
        }

        $hora = $request->get('hora_salida') ?? date('H:i');

        if ($rec->hora_ingreso && $hora <= $rec->hora_ingreso) {
            throw ValidationException::withMessages([
                'hora_salida' => 'La hora de salida debe ser mayor a la de ingreso.'
            ]);
        }

        $rec->hora_salida = $hora;
        $rec->save();

        $this->logBitacora($request, 'Asistencia', 'salida', 'AsistenciaDocente', "Marcó salida {$rec->id}");

        return (new AsistenciaResource($rec->fresh()->load(['docente','programacion'])))
            ->additional(['message' => 'Salida marcada']);
    }

    public function sesionesDocente(Request $request)
    {
        $payload = $request->validate([
            'fecha' => ['required','date'],
            'gestion_id' => ['nullable','integer','min:1'],
            'docente_id' => ['nullable','integer','min:1'],
        ]);

        $fecha = $payload['fecha'];
        $diaSem = Carbon::parse($fecha)->isoWeekday(); // 1 (lunes) .. 7 (domingo)
        $docenteId = $payload['docente_id'] ?? $this->resolvePersonaId($request);

        $query = DB::table('academia.horario as h')
            ->join('academia.grupo as g','g.id_grupo','=','h.grupo_id')
            ->leftJoin('academia.materia as m','m.id_materia','=','g.materia_id')
            ->leftJoin('academia.aula as a','a.id_aula','=','h.aula_id')
            ->selectRaw("
                h.id_horario,
                h.dia_semana,
                to_char(h.hora_inicio,'HH24:MI') AS hora_inicio,
                to_char(h.hora_fin,'HH24:MI') AS hora_fin,
                g.paralelo,
                g.turno,
                g.gestion_id,
                COALESCE(m.nombre, m.codigo) AS materia_nombre,
                m.codigo AS materia_codigo,
                COALESCE(a.codigo, 'Aula '||a.id_aula) AS aula_label
            ")
            ->where('h.dia_semana', $diaSem)
            ->orderBy('h.hora_inicio');

        if (!empty($payload['gestion_id'])) {
            $query->where('g.gestion_id', $payload['gestion_id']);
        }

        if ($docenteId && $this->tableExists('academia.programacion')) {
            $query->join('academia.programacion as p','p.grupo_id','=','g.id_grupo')
                  ->where('p.docente_id', $docenteId);
        }

        $rows = $query->get();

        $status = null;
        if ($docenteId) {
            $rec = AsistenciaDocente::where('docente_id', $docenteId)
                ->whereDate('fecha', $fecha)
                ->first();
            if ($rec) {
                $status = $rec->estado;
            }
        }

        $sessions = $rows->map(function ($row) use ($fecha, $status) {
            return [
                'id_horario'   => (int) $row->id_horario,
                'dia_semana'   => (int) $row->dia_semana,
                'fecha'        => $fecha,
                'hora_inicio'  => $row->hora_inicio,
                'hora_fin'     => $row->hora_fin,
                'aula_label'   => $row->aula_label,
                'grupo_label'  => trim(($row->materia_codigo ?? 'GRUPO').' · '.$row->paralelo.' · '.$row->turno),
                'materia_label'=> $row->materia_nombre ?? $row->materia_codigo,
                'estado'       => $status ?? 'pendiente',
            ];
        });

        return response()->json(['data' => $sessions]);
    }

    public function marcarDocente(Request $request)
    {
        if (!$this->canMarkSelf($request) && !$this->canManage($request)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $data = $request->validate([
            'horario_id'   => ['required','integer','min:1'],
            'fecha'        => ['required','date'],
            'estado'       => ['required','in:presente,ausente,justificado'],
            'justificacion'=> ['nullable','string','max:255'],
        ]);

        $docenteId = $this->resolvePersonaId($request);
        if (!$docenteId) {
            return response()->json(['message' => 'docente_id requerido'], 422);
        }

        $horario = DB::table('academia.horario as h')
            ->join('academia.grupo as g','g.id_grupo','=','h.grupo_id')
            ->select('h.id_horario','g.gestion_id')
            ->where('h.id_horario', $data['horario_id'])
            ->first();

        if (!$horario) {
            return response()->json(['message' => 'Horario no encontrado'], 404);
        }

        $record = AsistenciaDocente::updateOrCreate(
            [
                'docente_id' => $docenteId,
                'fecha'      => $data['fecha'],
            ],
            [
                'gestion_id'     => $horario->gestion_id,
                'estado'         => $data['estado'],
                'hora_ingreso'   => $data['estado'] === 'presente' ? now()->format('H:i') : null,
                'hora_salida'    => null,
                'programacion_id'=> $horario->id_horario,
                'fuente'         => 'docente',
                'observacion'    => $data['justificacion'] ?? null,
            ]
        );

        $this->logBitacora(
            $docenteId,
            'Docente',
            'Asistencia',
            'marcar',
            "Horario:{$horario->id_horario}",
            'OK',
            null,
            $data
        );

        return response()->json(['data' => ['ok' => true]]);
    }

    private function validateTimes(array $data): void
    {
        $estado = $data['estado'] ?? 'presente';
        $ing    = $data['hora_ingreso'] ?? null;
        $sal    = $data['hora_salida'] ?? null;

        if (in_array($estado, ['presente','tarde'])) {
            if (!$ing) {
                throw ValidationException::withMessages([
                    'hora_ingreso' => 'Para estado presente/tarde, hora_ingreso es obligatoria.'
                ]);
            }
        }
        if ($ing && $sal && $sal <= $ing) {
            throw ValidationException::withMessages([
                'hora_salida' => 'hora_salida debe ser mayor que hora_ingreso.'
            ]);
        }
    }
}
