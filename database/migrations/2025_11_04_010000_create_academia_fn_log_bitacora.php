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
        DECLARE
          v_col_persona TEXT;
        BEGIN
          IF to_regclass('academia.bitacora') IS NOT NULL THEN
            SELECT column_name INTO v_col_persona
            FROM information_schema.columns
            WHERE table_schema='academia' AND table_name='bitacora'
              AND column_name IN ('persona_id','persona')
            ORDER BY CASE column_name WHEN 'persona_id' THEN 1 ELSE 2 END
            LIMIT 1;

            IF v_col_persona = 'persona_id' THEN
              EXECUTE format('INSERT INTO academia.bitacora(persona_id, rol, modulo, accion, detalle, estado, payload, extra, created_at)
                              VALUES ($1,$2,$3,$4,$5,$6,$7,$8,now())')
              USING p_persona, p_rol, p_modulo, p_accion, p_detalle, p_estado, p_payload, p_extra;
            ELSIF v_col_persona = 'persona' THEN
              EXECUTE format('INSERT INTO academia.bitacora(persona, rol, modulo, accion, detalle, estado, payload, extra, created_at)
                              VALUES ($1,$2,$3,$4,$5,$6,$7,$8,now())')
              USING p_persona, p_rol, p_modulo, p_accion, p_detalle, p_estado, p_payload, p_extra;
            END IF;
          END IF;
        EXCEPTION WHEN undefined_column THEN
          -- swallow silently so app logic keeps working
          NULL;
        END;
        $$;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP FUNCTION IF EXISTS academia.fn_log_bitacora;');
    }
};
