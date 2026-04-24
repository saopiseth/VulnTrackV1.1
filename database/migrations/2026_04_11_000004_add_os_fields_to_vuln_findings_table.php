<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vuln_findings', function (Blueprint $table) {
            if (!Schema::hasColumn('vuln_findings', 'os_name')) {
                $table->string('os_name')->nullable()->after('os_detected');
            }
            if (!Schema::hasColumn('vuln_findings', 'os_family')) {
                $table->string('os_family')->nullable()->after('os_name');
            }
            if (!Schema::hasColumn('vuln_findings', 'os_confidence')) {
                $table->unsignedTinyInteger('os_confidence')->default(0)->after('os_family');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vuln_findings', function (Blueprint $table) {
            $cols = array_filter(['os_name', 'os_family', 'os_confidence'],
                fn($c) => Schema::hasColumn('vuln_findings', $c));
            if ($cols) {
                $table->dropColumn(array_values($cols));
            }
        });
    }
};
