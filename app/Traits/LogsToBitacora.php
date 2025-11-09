<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait LogsToBitacora
{
    protected function logBitacora($request, string $modulo, string $accion, string $entidad, string $descripcion = null, array $filtros = []): void

    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('bitacora')) return;

            $user = $request->user();
            $personaId = $user->persona_id ?? null;
            $actor = strtoupper($user->rol ?? 'CPD'); // asume CPD
            $ip = $request->ip();

            DB::table('bitacora')->insert([
                'persona_id' => $personaId,
                'fecha_hora' => now(),
                'actor'      => Str::limit($actor, 30, ''),
                'modulo'     => $modulo,
                'accion'     => $accion,
                'entidad'    => $entidad,
                'descripcion'=> $descripcion,
                'filtros'    => empty($filtros) ? null : json_encode($filtros, JSON_UNESCAPED_UNICODE),
                'ip_origen'  => $ip,
            ]);
        } catch (\Throwable $e) {
            // Silencioso: jamás romper por log.
        }
    }
}
