<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vuln_host_os', function (Blueprint $table) {
            if (!Schema::hasColumn('vuln_host_os', 'asset_criticality')) {
                $table->unsignedTinyInteger('asset_criticality')->nullable()->after('os_kernel');
            }
            if (!Schema::hasColumn('vuln_host_os', 'criticality_set_by')) {
                $table->foreignId('criticality_set_by')->nullable()->constrained('users')->nullOnDelete()->after('asset_criticality');
            }
            if (!Schema::hasColumn('vuln_host_os', 'criticality_set_at')) {
                $table->timestamp('criticality_set_at')->nullable()->after('criticality_set_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vuln_host_os', function (Blueprint $table) {
            if (Schema::hasColumn('vuln_host_os', 'criticality_set_by')) {
                $table->dropForeign(['criticality_set_by']);
            }
            $cols = array_filter(
                ['asset_criticality', 'criticality_set_by', 'criticality_set_at'],
                fn($c) => Schema::hasColumn('vuln_host_os', $c)
            );
            if ($cols) {
                $table->dropColumn(array_values($cols));
            }
        });
    }
};
