<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->string('whmcs_order_ref')->nullable()->after('hosting_account_ref');
            $table->string('whmcs_service_ref')->nullable()->after('whmcs_order_ref');
            // Documento JSON validado que se generó. Se reenvía a la instancia
            // al activarla (flujo WHMCS manual), sin regenerar.
            $table->json('document')->nullable()->after('whmcs_service_ref');
        });
    }

    public function down(): void
    {
        Schema::table('sites', fn (Blueprint $table) => $table->dropColumn(['whmcs_order_ref', 'whmcs_service_ref']));
    }
};
