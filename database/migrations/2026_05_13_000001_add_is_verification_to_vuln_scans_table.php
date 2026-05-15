<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vuln_scans', function (Blueprint $table) {
            $table->boolean('is_verification')->default(false)->after('is_baseline');
        });
    }

    public function down(): void
    {
        Schema::table('vuln_scans', function (Blueprint $table) {
            $table->dropColumn('is_verification');
        });
    }
};
