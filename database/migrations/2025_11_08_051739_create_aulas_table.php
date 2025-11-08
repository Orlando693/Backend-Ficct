<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('aulas', function (Blueprint $table) {
            $table->id(); // id
            $table->string('codigo', 50)->unique(); // p.ej. A-101
            $table->enum('tipo', ['TEORIA', 'LABORATORIO'])->default('TEORIA');
            $table->unsignedSmallInteger('capacidad')->default(30);

            // Si tienes tabla edificios, deja esta FK. Si no, deja nullable sin FK.
            if (Schema::hasTable('edificios')) {
                $table->foreignId('edificio_id')->nullable()->constrained('edificios')->nullOnDelete();
            } else {
                $table->unsignedBigInteger('edificio_id')->nullable()->index();
            }

            // Importante: usa mayúsculas para evitar el error del enum «activa».
            $table->enum('estado', ['ACTIVA', 'INACTIVA'])->default('ACTIVA');

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('aulas');
    }
};
