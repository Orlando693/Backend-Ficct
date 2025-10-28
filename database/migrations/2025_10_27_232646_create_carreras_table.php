<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Asegura el esquema y fija el search_path (usa tu .env: DB_SCHEMA=academia)
        $schema = env('DB_SCHEMA', 'public');
        DB::statement('CREATE SCHEMA IF NOT EXISTS "'.$schema.'"');
        DB::statement('SET search_path TO "'.$schema.'",public');

        // 👉 Si la tabla ya existe, NO volver a crearla (evita el 42P07)
        if (Schema::hasTable('carreras')) {
            return;
        }

        Schema::create('carreras', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nombre', 150);
            $table->string('sigla', 10);
            $table->string('estado', 8)->default('ACTIVA');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Respeta el search_path para borrar del esquema correcto
        $schema = env('DB_SCHEMA', 'public');
        DB::statement('SET search_path TO "'.$schema.'",public');

        Schema::dropIfExists('carreras');
    }
};
