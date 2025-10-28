<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Evita error si la tabla ya existe (por script previo)
        if (! Schema::hasTable('bitacora')) {
            Schema::create('bitacora', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('modulo', 80);
                $table->string('accion', 50);
                $table->string('descripcion', 255)->nullable();
                $table->string('usuario', 120)->nullable();
                $table->string('ip', 64)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        } else {
            // (opcional) asegura default de created_at si no lo tuviera
            try {
                DB::statement("ALTER TABLE bitacora ALTER COLUMN created_at SET DEFAULT CURRENT_TIMESTAMP");
            } catch (\Throwable $e) {
                // ignora si ya está seteado
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora');
    }
};
