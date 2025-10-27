<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Asegurar el esquema (PostgreSQL)
        DB::statement('CREATE SCHEMA IF NOT EXISTS academia');

        // Crear tabla en el esquema academia
        Schema::create('academia.carreras', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('sigla', 10)->unique();        // SIS, INF, IRT, etc.
            $table->string('estado', 8)->default('ACTIVA'); // ACTIVA | INACTIVA
            $table->timestamps();
            // índices útiles
            $table->index('nombre');
        });

        // CHECK para estado válido (opcional)
        DB::statement("ALTER TABLE academia.carreras
            ADD CONSTRAINT chk_estado_carrera
            CHECK (estado IN ('ACTIVA','INACTIVA'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('academia.carreras');
    }
};
