<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vuln_tracked', function (Blueprint $table) {
            $table->string('asset_owner', 255)->nullable()->after('os_family');
            $table->index(['assessment_id', 'asset_owner'], 'vt_aid_asset_owner');
        });
    }

    public function down(): void
    {
        Schema::table('vuln_tracked', function (Blueprint $table) {
            $table->dropIndex('vt_aid_asset_owner');
            $table->dropColumn('asset_owner');
        });
    }
};
