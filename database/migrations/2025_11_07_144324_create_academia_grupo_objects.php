<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
        -- 1) Esquema
        CREATE SCHEMA IF NOT EXISTS academia;

        -- 0) Catálogo básico de gestiones académicas
        CREATE TABLE IF NOT EXISTS academia.gestion_academica (
          id_gestion   BIGSERIAL PRIMARY KEY,
          anio         SMALLINT NOT NULL,
          periodo      SMALLINT NOT NULL CHECK (periodo BETWEEN 1 AND 3),
          fecha_inicio DATE,
          fecha_fin    DATE,
          estado       VARCHAR(12) NOT NULL DEFAULT 'ABIERTA' CHECK (estado IN ('ABIERTA','CERRADA')),
          UNIQUE (anio, periodo)
        );

        INSERT INTO academia.gestion_academica (anio, periodo, fecha_inicio, fecha_fin, estado)
        VALUES
          (2024, 1, '2024-02-01', '2024-06-30', 'CERRADA'),
          (2024, 2, '2024-08-01', '2024-12-15', 'ABIERTA'),
          (2025, 1, '2025-02-01', '2025-06-30', 'ABIERTA')
        ON CONFLICT (anio, periodo) DO NOTHING;

        -- 2) Tabla GRUPO (sin FK duras; las añadimos abajo si existen los destinos)
        CREATE TABLE IF NOT EXISTS academia.grupo (
          id_grupo   BIGSERIAL PRIMARY KEY,
          gestion_id BIGINT   NOT NULL,
          materia_id BIGINT   NOT NULL,
          paralelo   VARCHAR(10) NOT NULL,
          turno      VARCHAR(10) NOT NULL CHECK (turno IN ('manana','tarde','noche')),
          capacidad  SMALLINT   NOT NULL CHECK (capacidad > 0),
          estado     VARCHAR(10) NOT NULL DEFAULT 'ACTIVO' CHECK (estado IN ('ACTIVO','INACTIVO'))
        );

        -- Unicidad: (gestion + materia + paralelo normalizado)
        CREATE UNIQUE INDEX IF NOT EXISTS uq_grupo_gmp
          ON academia.grupo(gestion_id, materia_id, UPPER(TRIM(paralelo)));

        -- Normalizador de paralelo
        CREATE OR REPLACE FUNCTION academia.fn_grupo_normaliza()
        RETURNS trigger
        LANGUAGE plpgsql
        AS $$
        BEGIN
          NEW.paralelo := UPPER(TRIM(NEW.paralelo));
          RETURN NEW;
        END$$;

        DROP TRIGGER IF EXISTS trg_biu_grupo_normaliza ON academia.grupo;
        CREATE TRIGGER trg_biu_grupo_normaliza
        BEFORE INSERT OR UPDATE ON academia.grupo
        FOR EACH ROW EXECUTE FUNCTION academia.fn_grupo_normaliza();

        -- 3) Agregar FKs solo si existen tablas destino
        DO $$
        BEGIN
          -- FK -> gestion_academica(id_gestion)
          IF to_regclass('academia.gestion_academica') IS NOT NULL THEN
            IF NOT EXISTS (
              SELECT 1 FROM pg_constraint c
              JOIN pg_class t ON t.oid = c.conrelid
              JOIN pg_namespace n ON n.oid = t.relnamespace
              WHERE n.nspname = 'academia' AND t.relname = 'grupo' AND c.conname = 'fk_grupo_gestion'
            ) THEN
              ALTER TABLE academia.grupo
              ADD CONSTRAINT fk_grupo_gestion
              FOREIGN KEY (gestion_id) REFERENCES academia.gestion_academica(id_gestion) ON DELETE RESTRICT;
            END IF;
          END IF;

          -- FK -> materia(id_materia)
          IF to_regclass('academia.materia') IS NOT NULL THEN
            IF NOT EXISTS (
              SELECT 1 FROM pg_constraint c
              JOIN pg_class t ON t.oid = c.conrelid
              JOIN pg_namespace n ON n.oid = t.relnamespace
              WHERE n.nspname = 'academia' AND t.relname = 'grupo' AND c.conname = 'fk_grupo_materia'
            ) THEN
              ALTER TABLE academia.grupo
              ADD CONSTRAINT fk_grupo_materia
              FOREIGN KEY (materia_id) REFERENCES academia.materia(id_materia) ON DELETE RESTRICT;
            END IF;
          END IF;
        END
        $$;

        -- 4) Vista de resumen (usa materia + gestión)
        CREATE OR REPLACE VIEW academia.vw_grupo_resumen AS
        SELECT
          g.id_grupo, g.gestion_id, g.materia_id, g.paralelo, g.turno, g.capacidad, g.estado,
          ga.anio, ga.periodo, ga.fecha_inicio, ga.fecha_fin, ga.estado AS gestion_estado,
          m.codigo AS materia_codigo,
          m.nombre AS materia_nombre,
          (m.codigo || ' - ' || m.nombre) AS materia_label
        FROM academia.grupo g
        JOIN academia.materia m ON m.id_materia = g.materia_id
        LEFT JOIN academia.gestion_academica ga ON ga.id_gestion = g.gestion_id;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
        DROP VIEW  IF EXISTS academia.vw_grupo_resumen;
        DROP TABLE IF EXISTS academia.grupo;
        DROP TABLE IF EXISTS academia.gestion_academica;
        SQL);
    }
};
