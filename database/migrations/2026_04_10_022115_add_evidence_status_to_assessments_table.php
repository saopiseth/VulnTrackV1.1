<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CRITERIA = [
        'system_architecture_review',
        'penetration_test',
        'security_hardening',
        'vulnerability_assessment',
        'secure_code_review',
        'antimalware_protection',
        'network_security',
        'security_monitoring',
        'system_access_matrix',
    ];

    public function up(): void
    {
        Schema::table('project_assessments', function (Blueprint $table) {
            foreach (self::CRITERIA as $field) {
                if (!Schema::hasColumn('project_assessments', "{$field}_evidence")) {
                    $table->string("{$field}_evidence")->nullable()->after("{$field}");
                }
                if (!Schema::hasColumn('project_assessments', "{$field}_status")) {
                    $table->enum("{$field}_status", ['Not Started', 'In Progress', 'Completed', 'N/A'])
                          ->default('Not Started')->after("{$field}_evidence");
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_assessments', function (Blueprint $table) {
            foreach (self::CRITERIA as $field) {
                $cols = array_filter(
                    ["{$field}_evidence", "{$field}_status"],
                    fn($c) => Schema::hasColumn('project_assessments', $c)
                );
                if ($cols) {
                    $table->dropColumn(array_values($cols));
                }
            }
        });
    }
};
