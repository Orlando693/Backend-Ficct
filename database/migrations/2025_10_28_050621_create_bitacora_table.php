<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bitacora', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('modulo', 80);
            $table->string('accion', 50);
            $table->string('descripcion', 255)->nullable();
            $table->string('usuario', 120)->nullable();
            $table->string('ip', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();
            // $table->timestamp('updated_at')->nullable(); // opcional
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora');
    }
};
