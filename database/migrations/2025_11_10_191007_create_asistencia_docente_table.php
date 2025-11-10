<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS academia');

        Schema::create('academia.asistencia_docente', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('gestion_id');    // academia.gestion_academica.id_gestion
            $table->unsignedBigInteger('docente_id');    // persona.id_persona
            $table->date('fecha');
            $table->string('estado', 12);                // presente | tarde | ausente | permiso
            $table->time('hora_ingreso')->nullable();
            $table->time('hora_salida')->nullable();
            $table->unsignedBigInteger('programacion_id')->nullable(); // academia.programacion.id
            $table->string('fuente', 10)->default('docente');          // docente | cpd | sistema
            $table->string('observacion', 255)->nullable();

            $table->timestamps();

            $table->unique(['docente_id','fecha'], 'uq_asistencia_docente_fecha');
            $table->index(['gestion_id','fecha']);
            $table->index(['estado']);
        });

        // FK segura a gestion_academica (siempre deberías tenerla ya)
        DB::statement("
            ALTER TABLE academia.asistencia_docente
            ADD CONSTRAINT academia_asistencia_docente_gestion_fk
            FOREIGN KEY (gestion_id) REFERENCES academia.gestion_academica(id_gestion) ON DELETE RESTRICT
        ");

        // === FK a programacion SOLO si existe la tabla ===
        $prog = DB::selectOne("SELECT to_regclass('academia.programacion') AS oid");
        if ($prog && $prog->oid !== null) {
            DB::statement("
                ALTER TABLE academia.asistencia_docente
                ADD CONSTRAINT academia_asistencia_docente_prog_fk
                FOREIGN KEY (programacion_id) REFERENCES academia.programacion(id) ON DELETE SET NULL
            ");
        } else {
            logger()->warning('[asistencia_docente] No se encontró academia.programacion; se omitió la FK. Añádela luego con otra migración.');
        }

        // === FK a persona detectando esquema/nombre ===
        $candidatos = [
            'academia.persona',
            'public.persona',
            'persona',
            'public.personas',
        ];
        $refPersona = null;
        foreach ($candidatos as $cand) {
            $row = DB::selectOne("SELECT to_regclass(?) AS oid", [$cand]);
            if ($row && $row->oid !== null) { $refPersona = $cand; break; }
        }
        if ($refPersona) {
            DB::statement("
                ALTER TABLE academia.asistencia_docente
                ADD CONSTRAINT academia_asistencia_docente_docente_fk
                FOREIGN KEY (docente_id) REFERENCES {$refPersona}(id_persona) ON DELETE RESTRICT
            ");
        } else {
            logger()->warning('[asistencia_docente] No se encontró tabla persona; se omitió la FK. Añádela luego con otra migración.');
        }

        // CHECKs
        DB::statement("ALTER TABLE academia.asistencia_docente
            ADD CONSTRAINT ck_asistencia_estado
            CHECK (estado IN ('presente','tarde','ausente','permiso'))");
        DB::statement("ALTER TABLE academia.asistencia_docente
            ADD CONSTRAINT ck_asistencia_fuente
            CHECK (fuente IN ('docente','cpd','sistema'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('academia.asistencia_docente');
    }
};
