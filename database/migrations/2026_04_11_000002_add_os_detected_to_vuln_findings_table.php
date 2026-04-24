<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('vuln_findings', 'os_detected')) {
            Schema::table('vuln_findings', function (Blueprint $table) {
                $table->string('os_detected')->nullable()->after('hostname');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vuln_findings', 'os_detected')) {
            Schema::table('vuln_findings', function (Blueprint $table) {
                $table->dropColumn('os_detected');
            });
        }
    }
};
