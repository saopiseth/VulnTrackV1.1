<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vuln_host_os', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('vuln_assessments')->cascadeOnDelete();
            $table->foreignId('scan_id')->nullable()->constrained('vuln_scans')->nullOnDelete();
            $table->string('ip_address');
            $table->string('hostname')->nullable();

            // Detected OS fields
            $table->string('os_name')->nullable();              // e.g. "Ubuntu 22.04 LTS"
            $table->string('os_family')->nullable();            // Windows / Linux / Unix / Other
            $table->unsignedTinyInteger('os_confidence')->default(0); // 0–100
            $table->json('detection_sources')->nullable();      // array of source labels used

            // Manual override
            $table->string('os_override')->nullable();          // manually set os_name
            $table->string('os_override_family')->nullable();   // manually set os_family
            $table->foreignId('os_override_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('os_override_at')->nullable();
            $table->text('os_override_note')->nullable();

            // History — JSON array of past detections [{os_name, os_family, confidence, scan_id, detected_at}]
            $table->json('os_history')->nullable();

            $table->timestamps();

            $table->unique(['assessment_id', 'ip_address']);
            $table->index(['assessment_id', 'os_family']);
            $table->index('os_family');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vuln_host_os');
    }
};
