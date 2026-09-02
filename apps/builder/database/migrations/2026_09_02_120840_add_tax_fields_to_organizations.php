<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('billing_tax_id')->nullable()->after('billing_country'); // RUC o CI
            $table->string('billing_company')->nullable()->after('billing_tax_id'); // razón social
        });
    }

    public function down(): void
    {
        Schema::table('organizations', fn (Blueprint $table) => $table->dropColumn(['billing_tax_id', 'billing_company']));
    }
};
