<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('vuln_assessments', 'scope_group_id')) {
            Schema::table('vuln_assessments', function (Blueprint $table) {
                $table->foreignId('scope_group_id')
                      ->nullable()
                      ->after('scanner_type')
                      ->constrained('assessment_scope_groups')
                      ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vuln_assessments', 'scope_group_id')) {
            Schema::table('vuln_assessments', function (Blueprint $table) {
                $table->dropForeign(['scope_group_id']);
                $table->dropColumn('scope_group_id');
            });
        }
    }
};
