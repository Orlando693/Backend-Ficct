<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
        CREATE SCHEMA IF NOT EXISTS academia;

        -- Asegúrate de tener academia.carreras(id_carrera, nombre, sigla, estado)
        -- y academia.materia(id_materia, codigo, nombre, creditos, estado)

        CREATE TABLE IF NOT EXISTS academia.materia_carrera(
          id_materia_carrera BIGSERIAL PRIMARY KEY,
          carrera_id BIGINT NOT NULL REFERENCES academia.carreras(id_carrera) ON DELETE RESTRICT,
          materia_id BIGINT NOT NULL REFERENCES academia.materia(id_materia) ON DELETE RESTRICT,
          plan      INTEGER NOT NULL DEFAULT 2025,
          semestre  SMALLINT NOT NULL CHECK (semestre BETWEEN 1 AND 12),
          tipo      VARCHAR(20) NOT NULL DEFAULT 'obligatoria',
          carga_teo SMALLINT NOT NULL DEFAULT 0,
          carga_pra SMALLINT NOT NULL DEFAULT 0,
          UNIQUE(carrera_id, materia_id, plan)
        );

        CREATE OR REPLACE VIEW academia.vw_plan_resumen AS
        SELECT
          mc.id_materia_carrera,
          mc.carrera_id, mc.materia_id, mc.plan, mc.semestre, mc.tipo, mc.carga_teo, mc.carga_pra,
          (m.codigo || ' · ' || m.nombre) AS materia_label
        FROM academia.materia_carrera mc
        JOIN academia.materia m ON m.id_materia = mc.materia_id;

        SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
        DROP VIEW IF EXISTS academia.vw_plan_resumen;
        DROP TABLE IF EXISTS academia.materia_carrera;
        SQL);
    }
};
