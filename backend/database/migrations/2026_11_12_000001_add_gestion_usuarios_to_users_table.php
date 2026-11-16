<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gestión web — usuarios del panel gestionados por el admin (sección Usuarios):
 * papelera (se pueden restaurar) y último acceso para ver quién usa el panel.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('ultimo_acceso_en')->nullable()->after('blocked');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ultimo_acceso_en', 'deleted_at']);
        });
    }
};
