<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vuln_findings', function (Blueprint $table) {
            // Kernel version (Linux) or build number (Windows), e.g. "5.4.0-74-generic" / "Build 19041"
            $table->string('os_kernel')->nullable()->after('os_confidence');
        });
    }

    public function down(): void
    {
        Schema::table('vuln_findings', function (Blueprint $table) {
            $table->dropColumn('os_kernel');
        });
    }
};
