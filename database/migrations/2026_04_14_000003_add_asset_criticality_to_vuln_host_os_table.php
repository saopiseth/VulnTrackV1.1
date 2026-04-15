<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vuln_host_os', function (Blueprint $table) {
            // 1=Mission-Critical, 2=Business-Critical, 3=Business Operational, 4=Administrative, 5=None-Bank
            $table->unsignedTinyInteger('asset_criticality')->nullable()->after('os_kernel');
            $table->foreignId('criticality_set_by')->nullable()->constrained('users')->nullOnDelete()->after('asset_criticality');
            $table->timestamp('criticality_set_at')->nullable()->after('criticality_set_by');
        });
    }

    public function down(): void
    {
        Schema::table('vuln_host_os', function (Blueprint $table) {
            $table->dropForeign(['criticality_set_by']);
            $table->dropColumn(['asset_criticality', 'criticality_set_by', 'criticality_set_at']);
        });
    }
};
