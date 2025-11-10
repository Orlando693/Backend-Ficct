<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
        -- =========================================
        -- 1) Esquema
        -- =========================================
        CREATE SCHEMA IF NOT EXISTS academia;

        -- =========================================
        -- 2) Tabla AULA (solo si no existe)
        --    (compatibles con tu controlador de Aulas)
        -- =========================================
        CREATE TABLE IF NOT EXISTS academia.aula (
          id_aula    BIGSERIAL PRIMARY KEY,
          codigo     VARCHAR(30)  NOT NULL UNIQUE,
          capacidad  SMALLINT     NOT NULL CHECK (capacidad > 0),
          tipo       VARCHAR(20)  NOT NULL DEFAULT 'teoria' CHECK (tipo IN ('teoria','laboratorio','auditorio','otros')),
          estado     VARCHAR(10)  NOT NULL DEFAULT 'ACTIVA' CHECK (estado IN ('ACTIVA','INACTIVA')),
          created_at TIMESTAMPTZ  DEFAULT now(),
          updated_at TIMESTAMPTZ  DEFAULT now()
        );

        -- Normalizador simple (código mayúsculas/trim)
        CREATE OR REPLACE FUNCTION academia.fn_aula_normaliza()
        RETURNS trigger
        LANGUAGE plpgsql
        AS $$
        BEGIN
          NEW.codigo := UPPER(TRIM(NEW.codigo));
          RETURN NEW;
        END;
        $$;

        DROP TRIGGER IF EXISTS trg_biu_aula_normaliza ON academia.aula;
        CREATE TRIGGER trg_biu_aula_normaliza
        BEFORE INSERT OR UPDATE ON academia.aula
        FOR EACH ROW EXECUTE FUNCTION academia.fn_aula_normaliza();

        -- =========================================
        -- 3) Tabla HORARIO (bloques por grupo y aula)
        -- =========================================
        CREATE TABLE IF NOT EXISTS academia.horario(
          id_horario  BIGSERIAL PRIMARY KEY,
          grupo_id    BIGINT   NOT NULL REFERENCES academia.grupo(id_grupo) ON DELETE CASCADE,
          aula_id     BIGINT   NOT NULL REFERENCES academia.aula(id_aula)   ON DELETE RESTRICT,
          dia_semana  SMALLINT NOT NULL CHECK (dia_semana BETWEEN 1 AND 7),
          hora_inicio TIME     NOT NULL,
          hora_fin    TIME     NOT NULL,
          estado      VARCHAR(10) NOT NULL DEFAULT 'ACTIVO',
          created_at  TIMESTAMPTZ DEFAULT now(),
          updated_at  TIMESTAMPTZ DEFAULT now()
        );

        -- =========================================
        -- 4) Validaciones: rango + no solapes
        -- =========================================
        CREATE OR REPLACE FUNCTION academia.fn_horario_validaciones()
        RETURNS trigger
        LANGUAGE plpgsql
        AS $$
        DECLARE
          v_gestion BIGINT;
        BEGIN
          IF NEW.hora_inicio >= NEW.hora_fin THEN
            RAISE EXCEPTION 'hora_inicio debe ser menor que hora_fin';
          END IF;

          IF NEW.dia_semana < 1 OR NEW.dia_semana > 7 THEN
            RAISE EXCEPTION 'dia_semana fuera de 1..7';
          END IF;

          SELECT g.gestion_id INTO v_gestion
          FROM academia.grupo g
          WHERE g.id_grupo = NEW.grupo_id;

          -- Choque por grupo
          IF EXISTS (
            SELECT 1
            FROM academia.horario h
            WHERE h.grupo_id = NEW.grupo_id
              AND h.dia_semana = NEW.dia_semana
              AND h.id_horario <> COALESCE(NEW.id_horario,0)
              AND (NEW.hora_inicio < h.hora_fin) AND (NEW.hora_fin > h.hora_inicio)
          ) THEN
            RAISE EXCEPTION 'Choque de horario en el mismo grupo';
          END IF;

          -- Choque por aula dentro de la misma gestión
          IF EXISTS (
            SELECT 1
            FROM academia.horario h
            JOIN academia.grupo g ON g.id_grupo = h.grupo_id
            WHERE g.gestion_id = v_gestion
              AND h.aula_id = NEW.aula_id
              AND h.dia_semana = NEW.dia_semana
              AND h.id_horario <> COALESCE(NEW.id_horario,0)
              AND (NEW.hora_inicio < h.hora_fin) AND (NEW.hora_fin > h.hora_inicio)
          ) THEN
            RAISE EXCEPTION 'Choque de aula: bloque ocupado';
          END IF;

          RETURN NEW;
        END;
        $$;

        DROP TRIGGER IF EXISTS trg_biu_horario_valid ON academia.horario;
        CREATE TRIGGER trg_biu_horario_valid
        BEFORE INSERT OR UPDATE ON academia.horario
        FOR EACH ROW EXECUTE FUNCTION academia.fn_horario_validaciones();

        -- =========================================
        -- 5) Vista de apoyo al front
        -- =========================================
        CREATE OR REPLACE VIEW academia.vw_horario_resumen AS
        SELECT
          h.id_horario, h.grupo_id, h.aula_id, h.dia_semana, h.hora_inicio, h.hora_fin,
          a.codigo AS aula_codigo,
          COALESCE(a.codigo, 'Aula '||a.id_aula) AS aula_label
        FROM academia.horario h
        LEFT JOIN academia.aula a ON a.id_aula = h.aula_id;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
        DROP VIEW    IF EXISTS academia.vw_horario_resumen;
        DROP TRIGGER IF EXISTS trg_biu_horario_valid ON academia.horario;
        DROP FUNCTION IF EXISTS academia.fn_horario_validaciones();
        DROP TABLE   IF EXISTS academia.horario;

        -- Si quieres conservar AULA, comenta la línea siguiente
        -- (solo se creó aquí para resolver la FK)
        -- DROP TRIGGER IF EXISTS trg_biu_aula_normaliza ON academia.aula;
        -- DROP FUNCTION IF EXISTS academia.fn_aula_normaliza();
        -- DROP TABLE   IF EXISTS academia.aula;
        SQL);
    }
};
