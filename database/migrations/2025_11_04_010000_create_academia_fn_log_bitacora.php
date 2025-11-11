<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
        CREATE SCHEMA IF NOT EXISTS academia;

        CREATE OR REPLACE FUNCTION academia.fn_log_bitacora(
            p_persona BIGINT,
            p_rol TEXT,
            p_modulo TEXT,
            p_accion TEXT,
            p_detalle TEXT,
            p_estado TEXT DEFAULT NULL,
            p_payload JSONB DEFAULT NULL,
            p_extra JSONB DEFAULT NULL
        )
        RETURNS void
        LANGUAGE plpgsql
        AS $$
        BEGIN
          IF to_regclass('academia.bitacora') IS NOT NULL THEN
            BEGIN
              INSERT INTO academia.bitacora(persona_id, rol, modulo, accion, detalle, estado, payload, extra, created_at)
              VALUES (p_persona, p_rol, p_modulo, p_accion, p_detalle, p_estado, p_payload, p_extra, now());
            EXCEPTION WHEN undefined_column THEN
              INSERT INTO academia.bitacora(persona, rol, modulo, accion, detalle, estado, payload, extra, created_at)
              VALUES (p_persona, p_rol, p_modulo, p_accion, p_detalle, p_estado, p_payload, p_extra, now());
            END;
          END IF;
        END;
        $$;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP FUNCTION IF EXISTS academia.fn_log_bitacora;');
    }
};
