<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Asegurar el esquema
        DB::statement('CREATE SCHEMA IF NOT EXISTS academia');

        // Crear la tabla en el esquema academia
        Schema::create('academia.bitacora', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('modulo', 80);
            $table->string('accion', 50);
            $table->string('descripcion', 255)->nullable();
            $table->string('usuario', 120)->nullable();
            $table->string('ip', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['modulo']);
            $table->index(['accion']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academia.bitacora');
    }
};
