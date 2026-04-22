<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vuln_assessments', function (Blueprint $table) {
            $table->unsignedBigInteger('sla_policy_id')->nullable()->after('scope_group_id');
            $table->foreign('sla_policy_id')->references('id')->on('sla_policies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vuln_assessments', function (Blueprint $table) {
            $table->dropForeign(['sla_policy_id']);
            $table->dropColumn('sla_policy_id');
        });
    }
};
