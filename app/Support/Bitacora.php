<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Bitacora
{
    /**
     * Registra eventos en la función almacenada academia.fn_log_bitacora.
     * Es tolerante: jamás deja caer la petición si la función no existe.
     */
    public static function log(
        $personaId,
        ?string $actor,
        string $modulo,
        string $accion,
        string $entidad,
        string $resultado = 'OK',
        ?string $descripcion = null,
        $extras = null
    ): void {
        try {
            if (!function_exists('\DB')) {
                return;
            }

            $actor = $actor ? Str::limit(strtoupper($actor), 30, '') : null;
            $payload = is_array($extras) ? json_encode($extras, JSON_UNESCAPED_UNICODE) : $extras;

            DB::select('select academia.fn_log_bitacora(?, ?, ?, ?, ?, ?, ?, ?)', [
                $personaId,
                $actor,
                $modulo,
                $accion,
                $entidad,
                $resultado,
                $descripcion,
                $payload,
            ]);
        } catch (\Throwable $e) {
            // Silencioso por diseño: nunca interrumpir el flujo por la bitácora.
        }
    }
}
