<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vuln_findings', function (Blueprint $table) {
            $table->char('vuln_key', 40)->nullable()->after('plugin_id');
            $table->index('vuln_key');
        });
    }

    public function down(): void
    {
        Schema::table('vuln_findings', function (Blueprint $table) {
            $table->dropIndex(['vuln_key']);
            $table->dropColumn('vuln_key');
        });
    }
};
