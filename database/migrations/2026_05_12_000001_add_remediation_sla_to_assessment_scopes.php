<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_scopes', function (Blueprint $table) {
            $table->string('remediation_sla', 100)->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('assessment_scopes', function (Blueprint $table) {
            $table->dropColumn('remediation_sla');
        });
    }
};
