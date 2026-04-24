<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('vuln_host_os', 'os_kernel')) {
            Schema::table('vuln_host_os', function (Blueprint $table) {
                $table->string('os_kernel')->nullable()->after('os_confidence');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vuln_host_os', 'os_kernel')) {
            Schema::table('vuln_host_os', function (Blueprint $table) {
                $table->dropColumn('os_kernel');
            });
        }
    }
};
