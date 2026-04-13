<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Master tracking table ─────────────────────────────────────────
        // One row per unique (assessment_id + ip_address + plugin_id).
        // Mutated on every scan upload by the comparison engine.
        Schema::create('vuln_tracked', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assessment_id');
            $table->foreign('assessment_id')->references('id')->on('vuln_assessments')->cascadeOnDelete();

            // Identity key
            $table->string('ip_address', 45);
            $table->string('hostname')->nullable();
            $table->string('plugin_id', 50);
            $table->string('cve', 50)->nullable();

            // Vulnerability details (kept current from latest scan)
            $table->string('vuln_name', 500);
            $table->text('description')->nullable();
            $table->text('remediation_text')->nullable();
            $table->enum('severity', ['Critical', 'High', 'Medium', 'Low', 'Info']);
            $table->string('port', 10)->nullable();
            $table->string('protocol', 10)->nullable();
            $table->string('vuln_category', 100)->nullable();
            $table->string('affected_component', 255)->nullable();

            // OS info (from latest scan)
            $table->string('os_detected', 255)->nullable();
            $table->string('os_name', 255)->nullable();
            $table->string('os_family', 50)->nullable();

            // Lifecycle tracking
            // New     = first time seen in this project
            // Pending = still present in the latest scan
            // Resolved = no longer found in the latest scan
            $table->enum('tracking_status', ['New', 'Pending', 'Resolved'])->default('New');
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('first_scan_id');
            $table->unsignedBigInteger('last_scan_id');
            $table->foreign('first_scan_id')->references('id')->on('vuln_scans');
            $table->foreign('last_scan_id')->references('id')->on('vuln_scans');

            $table->timestamps();

            // One record per ip+vuln per assessment — the unique tracking key
            $table->unique(['assessment_id', 'ip_address', 'plugin_id'], 'uq_tracked_key');

            $table->index('assessment_id');
            $table->index('tracking_status');
            $table->index('severity');
            $table->index('ip_address');
            $table->index('last_seen_at');
        });

        // ── Audit / history table ─────────────────────────────────────────
        // Append-only. Every state change is recorded here. Never updated.
        Schema::create('vuln_tracked_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tracked_id');
            $table->foreign('tracked_id')->references('id')->on('vuln_tracked')->cascadeOnDelete();
            $table->unsignedBigInteger('scan_id');
            $table->foreign('scan_id')->references('id')->on('vuln_scans');

            $table->enum('event_type', [
                'created',          // first time this ip+vuln was detected
                'still_present',    // detected again, nothing changed
                'severity_changed', // severity went up or down
                'status_changed',   // New→Pending transition
                'reappeared',       // was Resolved, detected again
                'resolved',         // missing from latest scan → auto-resolved
            ]);

            $table->enum('prev_status', ['New', 'Pending', 'Resolved'])->nullable();
            $table->enum('new_status',  ['New', 'Pending', 'Resolved'])->nullable();
            $table->enum('prev_severity', ['Critical', 'High', 'Medium', 'Low', 'Info'])->nullable();
            $table->enum('new_severity',  ['Critical', 'High', 'Medium', 'Low', 'Info'])->nullable();
            $table->text('note')->nullable();
            $table->timestamp('changed_at')->useCurrent();

            $table->index('tracked_id');
            $table->index('scan_id');
            $table->index('event_type');
            $table->index('changed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vuln_tracked_history');
        Schema::dropIfExists('vuln_tracked');
    }
};
