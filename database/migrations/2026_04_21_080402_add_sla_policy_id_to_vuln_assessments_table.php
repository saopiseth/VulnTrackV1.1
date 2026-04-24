<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('vuln_assessments', 'sla_policy_id')) {
            Schema::table('vuln_assessments', function (Blueprint $table) {
                $table->unsignedBigInteger('sla_policy_id')->nullable()->after('scope_group_id');
                $table->foreign('sla_policy_id')->references('id')->on('sla_policies')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vuln_assessments', 'sla_policy_id')) {
            Schema::table('vuln_assessments', function (Blueprint $table) {
                $table->dropForeign(['sla_policy_id']);
                $table->dropColumn('sla_policy_id');
            });
        }
    }
};
