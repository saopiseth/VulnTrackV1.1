<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vuln_findings', function (Blueprint $table) {
            $table->decimal('cvss_score', 4, 1)->nullable()->after('cve');
        });

        Schema::table('vuln_tracked', function (Blueprint $table) {
            $table->decimal('cvss_score', 4, 1)->nullable()->after('cve');
        });
    }

    public function down(): void
    {
        Schema::table('vuln_findings', function (Blueprint $table) {
            $table->dropColumn('cvss_score');
        });

        Schema::table('vuln_tracked', function (Blueprint $table) {
            $table->dropColumn('cvss_score');
        });
    }
};
