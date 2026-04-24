<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vuln_findings', function (Blueprint $table) {
            if (!Schema::hasColumn('vuln_findings', 'vuln_category')) {
                $table->string('vuln_category')->nullable()->after('os_detected');
            }
            if (!Schema::hasColumn('vuln_findings', 'affected_component')) {
                $table->string('affected_component')->nullable()->after('vuln_category');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vuln_findings', function (Blueprint $table) {
            $cols = array_filter(['vuln_category', 'affected_component'],
                fn($c) => Schema::hasColumn('vuln_findings', $c));
            if ($cols) {
                $table->dropColumn(array_values($cols));
            }
        });
    }
};
