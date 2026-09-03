<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Credenciales del dueño para el CMS de la instancia publicada. Se generan al
 * publicar y se muestran una vez al cliente. El builder no las usa para nada
 * más: la sesión del CMS vive en la instancia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->string('cms_email')->nullable();
            $table->string('cms_password')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['cms_email', 'cms_password']);
        });
    }
};
