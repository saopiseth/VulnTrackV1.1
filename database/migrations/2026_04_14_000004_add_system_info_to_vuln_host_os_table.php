<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vuln_host_os', function (Blueprint $table) {
            $table->string('system_name')->nullable()->after('criticality_set_at');
            $table->string('system_owner')->nullable()->after('system_name');
        });
    }

    public function down(): void
    {
        Schema::table('vuln_host_os', function (Blueprint $table) {
            $table->dropColumn(['system_name', 'system_owner']);
        });
    }
};
