<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vuln_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_id')->constrained('vuln_scans')->cascadeOnDelete();
            $table->foreignId('assessment_id')->constrained('vuln_assessments')->cascadeOnDelete();
            $table->string('ip_address')->index();
            $table->string('hostname')->nullable();
            $table->string('plugin_id')->index();
            $table->string('cve')->nullable();
            $table->enum('severity', ['Critical', 'High', 'Medium', 'Low', 'Info']);
            $table->string('vuln_name');
            $table->text('description')->nullable();
            $table->text('remediation_text')->nullable();
            $table->string('port')->nullable();
            $table->string('protocol')->nullable();
            $table->text('plugin_output')->nullable();
            $table->timestamp('scan_timestamp')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vuln_findings');
    }
};
