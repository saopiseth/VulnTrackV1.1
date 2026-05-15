<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vuln_scans', function (Blueprint $table) {
            $table->string('scan_name')->nullable()->after('filename');
            $table->date('scan_date')->nullable()->after('scan_name');
        });
    }

    public function down(): void
    {
        Schema::table('vuln_scans', function (Blueprint $table) {
            $table->dropColumn(['scan_name', 'scan_date']);
        });
    }
};
