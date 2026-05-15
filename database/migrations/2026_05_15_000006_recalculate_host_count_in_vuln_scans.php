<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Recalculate host_count to only include hosts with Critical/High/Medium/Low findings.
     * Previously it counted all distinct IPs including those with only Informational findings,
     * which inflated the number vs what is shown in the Vulnerable Hosts display.
     */
    public function up(): void
    {
        DB::statement("
            UPDATE vuln_scans s
            SET host_count = (
                SELECT COUNT(DISTINCT ip_address)
                FROM vuln_findings
                WHERE scan_id = s.id
                  AND severity IN ('Critical', 'High', 'Medium', 'Low')
            )
        ");
    }

    public function down(): void
    {
        DB::statement("
            UPDATE vuln_scans s
            SET host_count = (
                SELECT COUNT(DISTINCT ip_address)
                FROM vuln_findings
                WHERE scan_id = s.id
            )
        ");
    }
};
