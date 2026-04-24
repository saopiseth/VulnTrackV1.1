<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vuln_assessments', function (Blueprint $table) {
            if (!Schema::hasColumn('vuln_assessments', 'period_start')) {
                $table->date('period_start')->nullable()->after('scan_date');
            }
            if (!Schema::hasColumn('vuln_assessments', 'period_end')) {
                $table->date('period_end')->nullable()->after('period_start');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vuln_assessments', function (Blueprint $table) {
            $cols = array_filter(['period_start', 'period_end'],
                fn($c) => Schema::hasColumn('vuln_assessments', $c));
            if ($cols) {
                $table->dropColumn(array_values($cols));
            }
        });
    }
};
