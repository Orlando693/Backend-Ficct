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
            CREATE UNIQUE INDEX IF NOT EXISTS uq_carreras_id_carrera ON academia.carreras(id_carrera);
            CREATE UNIQUE INDEX IF NOT EXISTS uq_carreras_id ON academia.carreras(id);
          END IF;

          IF to_regclass('academia.materia') IS NOT NULL THEN
            CREATE UNIQUE INDEX IF NOT EXISTS uq_materia_id_materia ON academia.materia(id_materia);
            CREATE UNIQUE INDEX IF NOT EXISTS uq_materia_id ON academia.materia(id);
          END IF;
        END
        $$;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
        DROP INDEX IF EXISTS uq_materia_id_materia;
        DROP INDEX IF EXISTS uq_materia_id;
        DROP INDEX IF EXISTS uq_carreras_id_carrera;
        DROP INDEX IF EXISTS uq_carreras_id;
        SQL);
    }
};
