<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->string('hosting_account_ref')->nullable()->after('runtime_site_ref');
            $table->string('runtime_version')->nullable()->after('hosting_account_ref');
            $table->string('live_fqdn')->nullable()->after('runtime_version');
            // subdomain_live | gtld_live | compy_pending
            $table->string('domain_status')->nullable()->after('live_fqdn');
            $table->string('pending_fqdn')->nullable()->after('domain_status');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('concept');              // "Publicación de … — plan …"
            $table->unsignedBigInteger('amount');   // unidad mínima (guaraníes: enteros)
            $table->string('currency', 3)->default('PYG');
            $table->string('status');               // paid | failed
            $table->string('gateway_ref')->nullable();
            $table->string('gateway')->default('whmcs');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('backoffice_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('kind');                 // domain_compy_register, generation_review, ...
            $table->text('note');
            $table->string('status')->default('open'); // open | done
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backoffice_tasks');
        Schema::dropIfExists('payments');
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['hosting_account_ref', 'runtime_version', 'live_fqdn', 'domain_status', 'pending_fqdn']);
        });
    }
};
