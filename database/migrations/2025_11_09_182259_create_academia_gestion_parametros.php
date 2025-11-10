<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
        -- 1) Esquema
        CREATE SCHEMA IF NOT EXISTS academia;

        -- 2) Tabla gestión_academica (periodos) -------------------------------
        CREATE TABLE IF NOT EXISTS academia.gestion_academica(
          id_gestion  BIGSERIAL PRIMARY KEY,
          anio        INTEGER    NOT NULL CHECK (anio BETWEEN 2000 AND 2100),
          periodo     SMALLINT   NOT NULL CHECK (periodo BETWEEN 1 AND 3),
          fecha_ini   DATE       NOT NULL,
          fecha_fin   DATE       NOT NULL,
          estado      VARCHAR(10) NOT NULL DEFAULT 'ACTIVO' CHECK (estado IN ('ACTIVO','INACTIVO')),
          CONSTRAINT uq_gestion UNIQUE(anio, periodo)
        );

        -- 3) Parámetros vigentes (una fila activa por vigencia) --------------
        CREATE TABLE IF NOT EXISTS academia.parametros_vigencia(
          id_parametro BIGSERIAL PRIMARY KEY,
          vigente_desde DATE NOT NULL DEFAULT CURRENT_DATE,
          vigente_hasta DATE NULL,
          duracion_bloque_min SMALLINT NOT NULL CHECK (duracion_bloque_min BETWEEN 5 AND 300),
          dias_habiles SMALLINT[] NOT NULL, -- valores 1..7
          turnos JSONB NOT NULL              -- [{ "turno":"manana","inicio":"07:00","fin":"11:30"}, ...]
        );

        -- Garantiza máximo una fila con vigente_hasta IS NULL (trigger simple)
        CREATE OR REPLACE FUNCTION academia.fn_parametros_uni_activo()
        RETURNS trigger LANGUAGE plpgsql AS $$
        BEGIN
          IF NEW.vigente_hasta IS NULL THEN
            UPDATE academia.parametros_vigencia SET vigente_hasta = CURRENT_DATE
            WHERE vigente_hasta IS NULL AND id_parametro <> COALESCE(NEW.id_parametro,-1);
          END IF;
          RETURN NEW;
        END$$;

        DROP TRIGGER IF EXISTS trg_biu_parametros_activo ON academia.parametros_vigencia;
        CREATE TRIGGER trg_biu_parametros_activo
        BEFORE INSERT OR UPDATE ON academia.parametros_vigencia
        FOR EACH ROW EXECUTE FUNCTION academia.fn_parametros_uni_activo();

        -- 4) Vista de parámetros actuales ------------------------------------
        CREATE OR REPLACE VIEW academia.vw_parametros_actuales AS
        SELECT p.*
        FROM academia.parametros_vigencia p
        WHERE p.vigente_hasta IS NULL
        ORDER BY p.id_parametro DESC
        LIMIT 1;

        SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
        DROP VIEW IF EXISTS academia.vw_parametros_actuales;
        DROP TRIGGER IF EXISTS trg_biu_parametros_activo ON academia.parametros_vigencia;
        DROP FUNCTION IF EXISTS academia.fn_parametros_uni_activo();
        DROP TABLE IF EXISTS academia.parametros_vigencia;
        DROP TABLE IF EXISTS academia.gestion_academica;
        -- Nota: NO dropeamos el schema academia para no afectar otras tablas
        SQL);
    }
};
