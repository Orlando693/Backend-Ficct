<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Agregar columnas sólo si no existen
        Schema::table('users', function (Blueprint $t) {
            if (!Schema::hasColumn('users', 'username')) {
                $t->string('username')->nullable()->unique()->after('name');
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $t->string('phone')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'role')) {
                $t->string('role')->default('Docente')->after('phone');
            }
            if (!Schema::hasColumn('users', 'status')) {
                $t->string('status')->default('PENDIENTE')->after('role');
            }
            if (!Schema::hasColumn('users', 'must_change_password')) {
                $t->boolean('must_change_password')->default(true)->after('status');
            }
            if (!Schema::hasColumn('users', 'deleted_at')) {
                $t->softDeletes();
            }
        });
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $t) {
            if (Schema::hasColumn('users', 'username')) $t->dropColumn('username');
            if (Schema::hasColumn('users', 'phone')) $t->dropColumn('phone');
            if (Schema::hasColumn('users', 'role')) $t->dropColumn('role');
            if (Schema::hasColumn('users', 'status')) $t->dropColumn('status');
            if (Schema::hasColumn('users', 'must_change_password')) $t->dropColumn('must_change_password');
            if (Schema::hasColumn('users', 'deleted_at')) $t->dropSoftDeletes();
        });
    }
};
