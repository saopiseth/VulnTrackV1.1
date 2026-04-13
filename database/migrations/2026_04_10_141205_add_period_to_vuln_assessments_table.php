<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vuln_assessments', function (Blueprint $table) {
            $table->date('period_start')->nullable()->after('scan_date');
            $table->date('period_end')->nullable()->after('period_start');
        });
    }

    public function down(): void
    {
        Schema::table('vuln_assessments', function (Blueprint $table) {
            $table->dropColumn(['period_start', 'period_end']);
        });
    }
};
