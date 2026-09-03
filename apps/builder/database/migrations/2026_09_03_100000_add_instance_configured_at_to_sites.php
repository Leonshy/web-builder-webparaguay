<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca de que la instancia de Plesk ya fue aprovisionada por la API (base de
 * datos, git, .env, SSL). Evita repetir el aprovisionamiento en los reintentos
 * de siembra.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->timestamp('instance_configured_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn('instance_configured_at');
        });
    }
};
