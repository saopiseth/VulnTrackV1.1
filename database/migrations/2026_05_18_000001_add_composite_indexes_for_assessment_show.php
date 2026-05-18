<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vuln_findings', function (Blueprint $table) {
            // Covers the dedup subquery: WHERE scan_id IN(...) AND severity IN(...)  GROUP BY vuln_key
            $table->index(['scan_id', 'severity', 'vuln_key'], 'idx_vf_scan_sev_key');

            // Covers host-comparison query: WHERE scan_id IN(...) GROUP BY ip_address
            $table->index(['scan_id', 'ip_address'], 'idx_vf_scan_ip');
        });

        Schema::table('vuln_tracked', function (Blueprint $table) {
            // Covers: WHERE assessment_id=? AND severity IN(?) AND tracking_status IN(?)
            $table->index(['assessment_id', 'severity', 'tracking_status'], 'idx_vt_assess_sev_status');

            // Covers: WHERE assessment_id=? AND ip_address IN(?) (scope-filtered queries)
            $table->index(['assessment_id', 'ip_address'], 'idx_vt_assess_ip');
        });
    }

    public function down(): void
    {
        Schema::table('vuln_findings', function (Blueprint $table) {
            $table->dropIndex('idx_vf_scan_sev_key');
            $table->dropIndex('idx_vf_scan_ip');
        });

        Schema::table('vuln_tracked', function (Blueprint $table) {
            $table->dropIndex('idx_vt_assess_sev_status');
            $table->dropIndex('idx_vt_assess_ip');
        });
    }
};
