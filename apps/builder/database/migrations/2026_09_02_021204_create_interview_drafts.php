<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El borrador de la entrevista guiada (§5.6 del legajo).
 *
 * Cada etapa persiste al cerrar: marca / propósito / contenido son bloques
 * JSON independientes con su propio estado. Abandonar en la etapa 2 no borra
 * la 1. Volver atrás y cambiar una etapa pasa las siguientes a "needs_review".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('stage')->default('welcome'); // welcome|brand|purpose|content|review|generating|done|needs_fix

            // Estado por etapa: empty | in_progress | confirmed | needs_review
            $table->string('brand_status')->default('empty');
            $table->string('purpose_status')->default('empty');
            $table->string('content_status')->default('empty');

            // Lo relevado en cada etapa. Se persiste al confirmar el resumen.
            $table->json('brand')->nullable();     // -> theme + notas de "asumido"
            $table->json('purpose')->nullable();   // -> industry, template, intención
            $table->json('content')->nullable();   // -> settings + insumos de secciones

            // Hilo conversacional (para reanudar la etapa donde quedó).
            $table->json('transcript')->nullable();

            $table->unsignedTinyInteger('palette_regenerations')->default(0);
            $table->string('last_error')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_drafts');
    }
};
