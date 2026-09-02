<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datos de facturación de la organización. Los necesita WHMCS para dar de
 * alta al cliente (AddClient) al publicar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('billing_phone')->nullable()->after('billing_email');
            $table->string('billing_address')->nullable()->after('billing_phone');
            $table->string('billing_city')->nullable()->after('billing_address');
            $table->string('billing_state')->nullable()->after('billing_city');
            $table->string('billing_postcode')->nullable()->after('billing_state');
            $table->string('billing_country', 2)->default('PY')->after('billing_postcode');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', fn (Blueprint $table) => $table->dropColumn([
            'billing_phone', 'billing_address', 'billing_city', 'billing_state', 'billing_postcode', 'billing_country',
        ]));
    }
};
