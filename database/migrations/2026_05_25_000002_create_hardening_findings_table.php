<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hardening_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hardening_assessment_id')->constrained()->cascadeOnDelete();
            $table->string('plugin_id')->index();
            $table->string('plugin_name');
            $table->string('plugin_family')->nullable();
            $table->text('description')->nullable();
            $table->text('solution')->nullable();
            $table->mediumText('plugin_output')->nullable();
            $table->string('severity')->nullable();
            $table->decimal('cvss_score', 4, 1)->nullable();
            $table->string('cve')->nullable();
            $table->string('port')->default('');
            $table->string('protocol')->default('');
            $table->string('service')->nullable();
            // Raw compliance result from Nessus (PASSED / FAILED / WARNING / ERROR)
            $table->string('compliance_result')->nullable();
            // Normalized status stored for display
            $table->enum('compliance_status', ['Compliant', 'Non-Compliant', 'Partially Compliant', 'Not Applicable'])->default('Non-Compliant');
            // sha1(plugin_id|port|protocol) — used to match findings across scans
            $table->string('finding_key', 40)->index();
            $table->timestamps();

            $table->index(['hardening_assessment_id', 'compliance_status']);
            $table->index(['hardening_assessment_id', 'finding_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hardening_findings');
    }
};
