<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jerarquía del builder (Tarea 3):
 *
 *   organizations → users → projects → sites
 *
 * En el MVP cada organización tiene un solo usuario y la capa es invisible en
 * la interfaz. NO se elimina: agregarla después obliga a migrar la titularidad
 * de todos los sitios en producción.
 *
 * `ai_usages` mide el consumo de IA desde la primera llamada, aunque los
 * créditos se vendan recién en la v1. Se mide en tokens; el cliente nunca ve
 * un token (eso se traduce a créditos en la capa de presentación).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('billing_email')->nullable();
            $table->unsignedBigInteger('credit_balance')->default(0); // créditos, no tokens
            $table->timestamps();
        });

        // users existe del scaffold; le agregamos la pertenencia a organización.
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('status')->default('draft'); // draft | interviewing | generated | published
            $table->timestamps();
        });

        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            // Id del sitio en site-runtime (base distinta, servidor del cliente).
            $table->string('runtime_site_ref')->nullable();
            $table->string('name');
            $table->string('published_domain')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');            // brand.palette, copy.section, image.hero, ...
            $table->string('model');             // claude-sonnet-5, ...
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedBigInteger('cost_microusd')->default(0); // costo calculado, en millonésimas de USD
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['organization_id', 'occurred_at']);
            $table->index(['project_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usages');
        Schema::dropIfExists('sites');
        Schema::dropIfExists('projects');
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('organization_id'));
        Schema::dropIfExists('organizations');
    }
};
