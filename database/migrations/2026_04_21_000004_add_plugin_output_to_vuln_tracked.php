<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('vuln_tracked', 'plugin_output')) {
            Schema::table('vuln_tracked', function (Blueprint $table) {
                $table->text('plugin_output')->nullable()->after('os_family');
            });
        }

        // Backfill from the finding that matches last_scan_id + ip + plugin
        DB::statement("
            UPDATE vuln_tracked
            SET plugin_output = (
                SELECT vf.plugin_output
                FROM vuln_findings vf
                WHERE vf.scan_id    = vuln_tracked.last_scan_id
                  AND vf.ip_address = vuln_tracked.ip_address
                  AND vf.plugin_id  = vuln_tracked.plugin_id
                LIMIT 1
            )
        ");
    }

    public function down(): void
    {
        if (Schema::hasColumn('vuln_tracked', 'plugin_output')) {
            Schema::table('vuln_tracked', function (Blueprint $table) {
                $table->dropColumn('plugin_output');
            });
        }
    }
};
