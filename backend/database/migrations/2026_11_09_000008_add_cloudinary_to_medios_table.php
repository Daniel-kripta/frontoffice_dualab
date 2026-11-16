<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gestión web — los archivos de la biblioteca pueden vivir en Cloudinary (como los
 * recursos de microproyectos en la app DuaLab): la BD solo guarda la URL y metadatos.
 * - proveedor: local | cloudinary
 * - ruta: en local, la ruta del disco; en Cloudinary, su public_id.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('medios', function (Blueprint $table) {
            $table->string('proveedor', 12)->default('local')->after('id');
            $table->string('url_externa', 500)->nullable()->after('ruta');
            $table->string('resource_type', 10)->nullable()->after('url_externa'); // image | video | raw
        });
    }

    public function down(): void
    {
        Schema::table('medios', function (Blueprint $table) {
            $table->dropColumn(['proveedor', 'url_externa', 'resource_type']);
        });
    }
};
