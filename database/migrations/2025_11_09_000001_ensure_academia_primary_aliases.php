<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
        DO $$
        BEGIN
          IF to_regclass('academia.carreras') IS NOT NULL THEN
            IF NOT EXISTS (
              SELECT 1 FROM information_schema.columns
              WHERE table_schema='academia' AND table_name='carreras' AND column_name='id_carrera'
            ) THEN
              ALTER TABLE academia.carreras
              ADD COLUMN id_carrera BIGINT GENERATED ALWAYS AS (id) STORED;
            END IF;
            CREATE UNIQUE INDEX IF NOT EXISTS uq_carreras_id_carrera ON academia.carreras(id_carrera);
            IF NOT EXISTS (
              SELECT 1 FROM information_schema.columns
              WHERE table_schema='academia' AND table_name='carreras' AND column_name='id'
            ) THEN
              ALTER TABLE academia.carreras
              ADD COLUMN id BIGINT GENERATED ALWAYS AS (id_carrera) STORED;
            END IF;
          END IF;

          IF to_regclass('academia.materia') IS NOT NULL THEN
            IF NOT EXISTS (
              SELECT 1 FROM information_schema.columns
              WHERE table_schema='academia' AND table_name='materia' AND column_name='id_materia'
            ) THEN
              ALTER TABLE academia.materia
              ADD COLUMN id_materia BIGINT GENERATED ALWAYS AS (id) STORED;
            END IF;
            CREATE UNIQUE INDEX IF NOT EXISTS uq_materia_id_materia ON academia.materia(id_materia);
            IF NOT EXISTS (
              SELECT 1 FROM information_schema.columns
              WHERE table_schema='academia' AND table_name='materia' AND column_name='id'
            ) THEN
              ALTER TABLE academia.materia
              ADD COLUMN id BIGINT GENERATED ALWAYS AS (id_materia) STORED;
            END IF;
          END IF;
        END
        $$;
        SQL);
    }

    public function down(): void
    {
        // no-op to avoid dropping alias columns in production
    }
};
