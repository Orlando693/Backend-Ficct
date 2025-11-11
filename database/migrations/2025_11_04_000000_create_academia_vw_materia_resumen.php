<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
        CREATE SCHEMA IF NOT EXISTS academia;

        CREATE OR REPLACE VIEW academia.vw_materia_resumen AS
        SELECT
          m.id_materia,
          m.id,
          m.codigo,
          m.nombre,
          m.creditos,
          m.estado,
          0::bigint AS materias_asociadas,
          0::bigint AS grupos_asociados
        FROM academia.materia m;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP VIEW IF EXISTS academia.vw_materia_resumen;');
    }
};
