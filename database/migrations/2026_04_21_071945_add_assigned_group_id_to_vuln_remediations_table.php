<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vuln_remediations', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_group_id')->nullable()->after('assigned_to');
            $table->foreign('assigned_group_id')->references('id')->on('user_groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vuln_remediations', function (Blueprint $table) {
            $table->dropForeign(['assigned_group_id']);
            $table->dropColumn('assigned_group_id');
        });
    }
};
