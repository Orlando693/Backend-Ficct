<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
        CREATE SCHEMA IF NOT EXISTS academia;

        -- =========================
        -- Tabla Carreras
        -- =========================
        CREATE TABLE IF NOT EXISTS academia.carreras (
          id_carrera BIGSERIAL PRIMARY KEY,
          id        BIGINT GENERATED ALWAYS AS (id_carrera) STORED,
          nombre    VARCHAR(150) NOT NULL,
          sigla     VARCHAR(15)  NOT NULL UNIQUE,
          estado    VARCHAR(10)  NOT NULL DEFAULT 'ACTIVA' CHECK (estado IN ('ACTIVA','INACTIVA')),
          created_at TIMESTAMPTZ DEFAULT now(),
          updated_at TIMESTAMPTZ DEFAULT now()
        );

        CREATE UNIQUE INDEX IF NOT EXISTS uq_carreras_id ON academia.carreras(id);
        CREATE UNIQUE INDEX IF NOT EXISTS uq_carreras_id_carrera ON academia.carreras(id_carrera);

        CREATE OR REPLACE FUNCTION academia.fn_carrera_normaliza()
        RETURNS trigger
        LANGUAGE plpgsql
        AS $$
        BEGIN
          NEW.sigla  := UPPER(TRIM(NEW.sigla));
          NEW.nombre := TRIM(NEW.nombre);
          RETURN NEW;
        END
        $$;

        DROP TRIGGER IF EXISTS trg_biu_carrera_normaliza ON academia.carreras;
        CREATE TRIGGER trg_biu_carrera_normaliza
        BEFORE INSERT OR UPDATE ON academia.carreras
        FOR EACH ROW EXECUTE FUNCTION academia.fn_carrera_normaliza();

        DO $$
        BEGIN
          IF NOT EXISTS (
            SELECT 1 FROM information_schema.columns
            WHERE table_schema='academia' AND table_name='carreras' AND column_name='id_carrera'
          ) AND EXISTS (
            SELECT 1 FROM information_schema.columns
            WHERE table_schema='academia' AND table_name='carreras' AND column_name='id'
          ) THEN
            ALTER TABLE academia.carreras
            ADD COLUMN id_carrera BIGINT GENERATED ALWAYS AS (id) STORED;
          END IF;

          IF NOT EXISTS (
            SELECT 1 FROM information_schema.columns
            WHERE table_schema='academia' AND table_name='carreras' AND column_name='id'
          ) AND EXISTS (
            SELECT 1 FROM information_schema.columns
            WHERE table_schema='academia' AND table_name='carreras' AND column_name='id_carrera'
          ) THEN
            ALTER TABLE academia.carreras
            ADD COLUMN id BIGINT GENERATED ALWAYS AS (id_carrera) STORED;
          END IF;
        END
        $$;

        -- =========================
        -- Tabla Materias
        -- =========================
        CREATE TABLE IF NOT EXISTS academia.materia (
          id_materia BIGSERIAL PRIMARY KEY,
          id         BIGINT GENERATED ALWAYS AS (id_materia) STORED,
          codigo     VARCHAR(30)  NOT NULL UNIQUE,
          nombre     VARCHAR(150) NOT NULL,
          creditos   SMALLINT     NOT NULL CHECK (creditos > 0),
          estado     VARCHAR(10)  NOT NULL DEFAULT 'ACTIVA' CHECK (estado IN ('ACTIVA','INACTIVA')),
          created_at TIMESTAMPTZ  DEFAULT now(),
          updated_at TIMESTAMPTZ  DEFAULT now()
        );

        CREATE UNIQUE INDEX IF NOT EXISTS uq_materia_id ON academia.materia(id);
        CREATE UNIQUE INDEX IF NOT EXISTS uq_materia_id_materia ON academia.materia(id_materia);

        CREATE OR REPLACE FUNCTION academia.fn_materia_normaliza()
        RETURNS trigger
        LANGUAGE plpgsql
        AS $$
        BEGIN
          NEW.codigo := UPPER(TRIM(NEW.codigo));
          NEW.nombre := TRIM(NEW.nombre);
          RETURN NEW;
        END
        $$;

        DROP TRIGGER IF EXISTS trg_biu_materia_normaliza ON academia.materia;
        CREATE TRIGGER trg_biu_materia_normaliza
        BEFORE INSERT OR UPDATE ON academia.materia
        FOR EACH ROW EXECUTE FUNCTION academia.fn_materia_normaliza();

        DO $$
        BEGIN
          IF NOT EXISTS (
            SELECT 1 FROM information_schema.columns
            WHERE table_schema='academia' AND table_name='materia' AND column_name='id_materia'
          ) AND EXISTS (
            SELECT 1 FROM information_schema.columns
            WHERE table_schema='academia' AND table_name='materia' AND column_name='id'
          ) THEN
            ALTER TABLE academia.materia
            ADD COLUMN id_materia BIGINT GENERATED ALWAYS AS (id) STORED;
          END IF;

          IF NOT EXISTS (
            SELECT 1 FROM information_schema.columns
            WHERE table_schema='academia' AND table_name='materia' AND column_name='id'
          ) AND EXISTS (
            SELECT 1 FROM information_schema.columns
            WHERE table_schema='academia' AND table_name='materia' AND column_name='id_materia'
          ) THEN
            ALTER TABLE academia.materia
            ADD COLUMN id BIGINT GENERATED ALWAYS AS (id_materia) STORED;
          END IF;
        END
        $$;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
        DROP TRIGGER IF EXISTS trg_biu_materia_normaliza ON academia.materia;
        DROP FUNCTION IF EXISTS academia.fn_materia_normaliza();
        DROP TABLE IF EXISTS academia.materia CASCADE;
        DROP INDEX IF EXISTS uq_materia_id_materia;

        DROP TRIGGER IF EXISTS trg_biu_carrera_normaliza ON academia.carreras;
        DROP FUNCTION IF EXISTS academia.fn_carrera_normaliza();
        DROP TABLE IF EXISTS academia.carreras CASCADE;
        DROP INDEX IF EXISTS uq_carreras_id_carrera;
        SQL);
    }
};
