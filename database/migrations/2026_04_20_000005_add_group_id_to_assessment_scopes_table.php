<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('assessment_scopes', 'group_id')) {
            Schema::table('assessment_scopes', function (Blueprint $table) {
                $table->foreignId('group_id')
                      ->nullable()
                      ->after('id')
                      ->constrained('assessment_scope_groups')
                      ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('assessment_scopes', 'group_id')) {
            Schema::table('assessment_scopes', function (Blueprint $table) {
                $table->dropForeignIdFor(\App\Models\AssessmentScopeGroup::class, 'group_id');
                $table->dropColumn('group_id');
            });
        }
    }
};
