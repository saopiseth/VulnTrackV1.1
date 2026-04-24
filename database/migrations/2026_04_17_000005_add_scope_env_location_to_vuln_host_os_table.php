<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vuln_host_os', function (Blueprint $table) {
            if (!Schema::hasColumn('vuln_host_os', 'identified_scope')) {
                $table->string('identified_scope', 50)->nullable()
                      ->after('system_owner')
                      ->comment('PCI | DMZ | Internal | External | Third-Party');
            }
            if (!Schema::hasColumn('vuln_host_os', 'environment')) {
                $table->string('environment', 20)->nullable()
                      ->after('identified_scope')
                      ->comment('PROD | UAT | STAGE');
            }
            if (!Schema::hasColumn('vuln_host_os', 'location')) {
                $table->string('location', 255)->nullable()
                      ->after('environment')
                      ->comment('DC | DR | Cloud');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vuln_host_os', function (Blueprint $table) {
            $cols = array_filter(['identified_scope', 'environment', 'location'],
                fn($c) => Schema::hasColumn('vuln_host_os', $c));
            if ($cols) {
                $table->dropColumn(array_values($cols));
            }
        });
    }
};
