<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Index name => [table, columns]
    private array $indexes = [
        // ── vuln_findings ───────────────────────────────────────────────────
        // From prior migration (2026_05_18_000001) — guard against re-run
        'idx_vf_scan_sev_key'     => ['vuln_findings', ['scan_id', 'severity', 'vuln_key']],
        'idx_vf_scan_ip'          => ['vuln_findings', ['scan_id', 'ip_address']],
        // New: dedup subquery joins on assessment_id + vuln_key + scan_id
        'idx_vf_assess_key_scan'  => ['vuln_findings', ['assessment_id', 'vuln_key', 'scan_id']],
        // New: scan-level severity+IP breakdown (ProcessScanUpload host counts)
        'idx_vf_scan_sev_ip'      => ['vuln_findings', ['scan_id', 'severity', 'ip_address']],
        // New: per-assessment IP+severity queries
        'idx_vf_assess_ip_sev'    => ['vuln_findings', ['assessment_id', 'ip_address', 'severity']],

        // ── vuln_tracked ────────────────────────────────────────────────────
        // From prior migration (2026_05_18_000001) — guard against re-run
        'idx_vt_assess_sev_status'  => ['vuln_tracked', ['assessment_id', 'severity', 'tracking_status']],
        'idx_vt_assess_ip'          => ['vuln_tracked', ['assessment_id', 'ip_address']],
        // New: vuln_key joins from vuln_findings dedup queries
        'idx_vt_assess_vuln_key'    => ['vuln_tracked', ['assessment_id', 'vuln_key']],
        // New: status-first variant for visibleTo / status-filter queries
        'idx_vt_assess_status_sev'  => ['vuln_tracked', ['assessment_id', 'tracking_status', 'severity']],
        // New: plugin+IP lookup (different prefix order from unique key uq_tracked_key)
        'idx_vt_assess_plugin_ip'   => ['vuln_tracked', ['assessment_id', 'plugin_id', 'ip_address']],

        // ── vuln_remediations ───────────────────────────────────────────────
        // New: RBAC visibleTo scope — WHERE assessment_id=? AND assigned_group_id IN(?)
        'idx_vr_assess_group'       => ['vuln_remediations', ['assessment_id', 'assigned_group_id']],

        // ── vuln_scans ──────────────────────────────────────────────────────
        // New: baseline/latest scan lookups
        'idx_vs_assess_baseline'    => ['vuln_scans', ['assessment_id', 'is_baseline', 'finding_count']],
        // New: upload_status polling
        'idx_vs_assess_status'      => ['vuln_scans', ['assessment_id', 'upload_status']],

        // ── assessment_scopes ───────────────────────────────────────────────
        // From prior migration (2026_05_18_000002) — guard against re-run
        'idx_as_group_ip'           => ['assessment_scopes', ['group_id', 'ip_address']],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $name => [$table, $columns]) {
            if (Schema::hasTable($table) && !Schema::hasIndex($table, $name)) {
                Schema::table($table, function (Blueprint $t) use ($columns, $name) {
                    $t->index($columns, $name);
                });
            }
        }
    }

    public function down(): void
    {
        // Only drop indexes added by THIS migration (not ones from earlier migrations).
        $ownedIndexes = [
            'idx_vf_assess_key_scan',
            'idx_vf_scan_sev_ip',
            'idx_vf_assess_ip_sev',
            'idx_vt_assess_vuln_key',
            'idx_vt_assess_status_sev',
            'idx_vt_assess_plugin_ip',
            'idx_vr_assess_group',
            'idx_vs_assess_baseline',
            'idx_vs_assess_status',
        ];

        foreach ($ownedIndexes as $name) {
            [$table] = $this->indexes[$name];
            if (Schema::hasTable($table) && Schema::hasIndex($table, $name)) {
                Schema::table($table, function (Blueprint $t) use ($name) {
                    $t->dropIndex($name);
                });
            }
        }
    }
};
