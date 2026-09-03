<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persistencia del CMS (Tarea 3).
 *
 * Jerarquía: sites → pages → sections. La titularidad (organización, proyecto)
 * vive en apps/builder; site-runtime corre en el servidor del cliente y no
 * conoce al builder (ADR-001).
 *
 * El JSON de configuración se persiste TAL CUAL, no normalizado en tablas por
 * tipo de sección: `sections.content` es un único JSON, sea el tipo que sea.
 * El esquema va a evolucionar y las secciones tienen formas distintas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            // Referencia opaca al proyecto en apps/builder. Sin foreign key:
            // son bases distintas y site-runtime no depende del builder.
            $table->string('builder_project_ref')->nullable()->index();
            $table->string('name');
            $table->string('template')->default('landing');
            $table->string('schema_version')->default('0.1');
            $table->json('theme');
            $table->json('settings');
            $table->json('layout')->nullable();
            $table->string('published_domain')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('title');
            $table->boolean('is_home')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('show_in_nav')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->json('seo')->nullable();
            $table->timestamps();

            $table->unique(['site_id', 'slug']);
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('variant');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('anchor')->nullable();
            $table->string('label')->nullable();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('background')->default('default');
            $table->json('background_image')->nullable();
            // Contrato de contenido completo, estable y persistido. Cambiar de
            // variante nunca puede perder contenido: los campos que la variante
            // nueva no usa quedan acá guardados.
            $table->json('content');
            $table->timestamps();
        });

        Schema::create('preview_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('label')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preview_tokens');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('sites');
    }
};
