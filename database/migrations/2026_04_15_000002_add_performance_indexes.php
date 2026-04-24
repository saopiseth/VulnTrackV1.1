<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('threat_intel_items', function (Blueprint $table) {
            if (!Schema::hasIndex('threat_intel_items', 'ti_type_idx'))       $table->index('type',       'ti_type_idx');
            if (!Schema::hasIndex('threat_intel_items', 'ti_status_idx'))     $table->index('status',     'ti_status_idx');
            if (!Schema::hasIndex('threat_intel_items', 'ti_severity_idx'))   $table->index('severity',   'ti_severity_idx');
            if (!Schema::hasIndex('threat_intel_items', 'ti_created_at_idx')) $table->index('created_at', 'ti_created_at_idx');
        });

        Schema::table('vuln_tracked', function (Blueprint $table) {
            if (!Schema::hasIndex('vuln_tracked', 'vt_plugin_id_idx'))  $table->index('plugin_id',    'vt_plugin_id_idx');
            if (!Schema::hasIndex('vuln_tracked', 'vt_cve_idx'))        $table->index('cve',          'vt_cve_idx');
            if (!Schema::hasIndex('vuln_tracked', 'vt_last_scan_idx'))  $table->index('last_scan_id', 'vt_last_scan_idx');
        });

        Schema::table('vuln_findings', function (Blueprint $table) {
            if (!Schema::hasIndex('vuln_findings', 'vf_assessment_idx'))          $table->index('assessment_id',               'vf_assessment_idx');
            if (!Schema::hasIndex('vuln_findings', 'vf_scan_idx'))                $table->index('scan_id',                     'vf_scan_idx');
            if (!Schema::hasIndex('vuln_findings', 'vf_assessment_severity_idx')) $table->index(['assessment_id', 'severity'],  'vf_assessment_severity_idx');
        });

        Schema::table('vuln_scans', function (Blueprint $table) {
            if (!Schema::hasIndex('vuln_scans', 'vs_assessment_idx'))         $table->index('assessment_id',                 'vs_assessment_idx');
            if (!Schema::hasIndex('vuln_scans', 'vs_assessment_created_idx')) $table->index(['assessment_id', 'created_at'], 'vs_assessment_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('threat_intel_items', function (Blueprint $table) {
            if (Schema::hasIndex('threat_intel_items', 'ti_type_idx'))       $table->dropIndex('ti_type_idx');
            if (Schema::hasIndex('threat_intel_items', 'ti_status_idx'))     $table->dropIndex('ti_status_idx');
            if (Schema::hasIndex('threat_intel_items', 'ti_severity_idx'))   $table->dropIndex('ti_severity_idx');
            if (Schema::hasIndex('threat_intel_items', 'ti_created_at_idx')) $table->dropIndex('ti_created_at_idx');
        });

        Schema::table('vuln_tracked', function (Blueprint $table) {
            if (Schema::hasIndex('vuln_tracked', 'vt_plugin_id_idx')) $table->dropIndex('vt_plugin_id_idx');
            if (Schema::hasIndex('vuln_tracked', 'vt_cve_idx'))       $table->dropIndex('vt_cve_idx');
            if (Schema::hasIndex('vuln_tracked', 'vt_last_scan_idx')) $table->dropIndex('vt_last_scan_idx');
        });

        Schema::table('vuln_findings', function (Blueprint $table) {
            if (Schema::hasIndex('vuln_findings', 'vf_assessment_idx'))          $table->dropIndex('vf_assessment_idx');
            if (Schema::hasIndex('vuln_findings', 'vf_scan_idx'))                $table->dropIndex('vf_scan_idx');
            if (Schema::hasIndex('vuln_findings', 'vf_assessment_severity_idx')) $table->dropIndex('vf_assessment_severity_idx');
        });

        Schema::table('vuln_scans', function (Blueprint $table) {
            if (Schema::hasIndex('vuln_scans', 'vs_assessment_idx'))         $table->dropIndex('vs_assessment_idx');
            if (Schema::hasIndex('vuln_scans', 'vs_assessment_created_idx')) $table->dropIndex('vs_assessment_created_idx');
        });
    }
};
