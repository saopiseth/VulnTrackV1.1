<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_assessments', function (Blueprint $table) {
            // Remove old columns
            $table->dropColumn(['hardening', 'penetration_testing']);

            // Add 9 criteria columns (default false = "No")
            $table->boolean('system_architecture_review')->default(false)->after('bcd_id');
            $table->boolean('penetration_test')->default(false)->after('system_architecture_review');
            $table->boolean('security_hardening')->default(false)->after('penetration_test');
            // vulnerability_assessment stays — just moved logically
            // secure_code_review stays
            $table->boolean('antimalware_protection')->default(false)->after('secure_code_review');
            $table->boolean('network_security')->default(false)->after('antimalware_protection');
            $table->boolean('security_monitoring')->default(false)->after('network_security');
            $table->boolean('system_access_matrix')->default(false)->after('security_monitoring');
        });
    }

    public function down(): void
    {
        Schema::table('project_assessments', function (Blueprint $table) {
            $table->dropColumn([
                'system_architecture_review',
                'penetration_test',
                'security_hardening',
                'antimalware_protection',
                'network_security',
                'security_monitoring',
                'system_access_matrix',
            ]);
            $table->boolean('hardening')->default(false);
            $table->boolean('penetration_testing')->default(false);
        });
    }
};
