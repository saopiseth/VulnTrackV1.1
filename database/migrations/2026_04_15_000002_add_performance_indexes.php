<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // threat_intel_items — filter columns
        Schema::table('threat_intel_items', function (Blueprint $table) {
            $table->index('type',       'ti_type_idx');
            $table->index('status',     'ti_status_idx');
            $table->index('severity',   'ti_severity_idx');
            $table->index('created_at', 'ti_created_at_idx');
        });

        // vuln_tracked — plugin_id used in findings() filter + cve for correlation subquery
        Schema::table('vuln_tracked', function (Blueprint $table) {
            $table->index('plugin_id',    'vt_plugin_id_idx');
            $table->index('cve',          'vt_cve_idx');
            $table->index('last_scan_id', 'vt_last_scan_idx');
        });

        // vuln_findings — assessment_id, severity (also scan_id FK)
        Schema::table('vuln_findings', function (Blueprint $table) {
            $table->index('assessment_id',              'vf_assessment_idx');
            $table->index('scan_id',                    'vf_scan_idx');
            $table->index(['assessment_id', 'severity'], 'vf_assessment_severity_idx');
        });

        // vuln_scans — assessment_id, created_at for latest() calls
        Schema::table('vuln_scans', function (Blueprint $table) {
            $table->index('assessment_id',              'vs_assessment_idx');
            $table->index(['assessment_id', 'created_at'], 'vs_assessment_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('threat_intel_items', function (Blueprint $table) {
            $table->dropIndex('ti_type_idx');
            $table->dropIndex('ti_status_idx');
            $table->dropIndex('ti_severity_idx');
            $table->dropIndex('ti_created_at_idx');
        });

        Schema::table('vuln_tracked', function (Blueprint $table) {
            $table->dropIndex('vt_plugin_id_idx');
            $table->dropIndex('vt_cve_idx');
            $table->dropIndex('vt_last_scan_idx');
        });

        Schema::table('vuln_findings', function (Blueprint $table) {
            $table->dropIndex('vf_assessment_idx');
            $table->dropIndex('vf_scan_idx');
            $table->dropIndex('vf_assessment_severity_idx');
        });

        Schema::table('vuln_scans', function (Blueprint $table) {
            $table->dropIndex('vs_assessment_idx');
            $table->dropIndex('vs_assessment_created_idx');
        });
    }
};
