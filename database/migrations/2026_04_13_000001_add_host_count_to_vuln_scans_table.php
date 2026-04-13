<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vuln_scans', function (Blueprint $table) {
            $table->unsignedInteger('host_count')->default(0)->after('finding_count');
        });

        // Backfill existing scans
        DB::statement("
            UPDATE vuln_scans
            SET host_count = (
                SELECT COUNT(DISTINCT ip_address)
                FROM vuln_findings
                WHERE vuln_findings.scan_id = vuln_scans.id
            )
        ");
    }

    public function down(): void
    {
        Schema::table('vuln_scans', function (Blueprint $table) {
            $table->dropColumn('host_count');
        });
    }
};
