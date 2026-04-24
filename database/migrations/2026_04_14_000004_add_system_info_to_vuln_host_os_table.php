<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vuln_host_os', function (Blueprint $table) {
            if (!Schema::hasColumn('vuln_host_os', 'system_name')) {
                $table->string('system_name')->nullable()->after('criticality_set_at');
            }
            if (!Schema::hasColumn('vuln_host_os', 'system_owner')) {
                $table->string('system_owner')->nullable()->after('system_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vuln_host_os', function (Blueprint $table) {
            $cols = array_filter(['system_name', 'system_owner'],
                fn($c) => Schema::hasColumn('vuln_host_os', $c));
            if ($cols) {
                $table->dropColumn(array_values($cols));
            }
        });
    }
};
