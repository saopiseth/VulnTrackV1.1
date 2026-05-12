<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_inventories', function (Blueprint $table) {
            $table->string('system_owner')->nullable()->after('system_name');
            $table->string('remediation_sla')->nullable()->after('system_owner');
        });

        // Convert stale ENUMs to plain strings so expanded scope/env values work
        DB::statement("ALTER TABLE asset_inventories MODIFY COLUMN identified_scope VARCHAR(50) NULL DEFAULT NULL");
        DB::statement("ALTER TABLE asset_inventories MODIFY COLUMN environment VARCHAR(50) NULL DEFAULT NULL");
    }

    public function down(): void
    {
        Schema::table('asset_inventories', function (Blueprint $table) {
            $table->dropColumn(['system_owner', 'remediation_sla']);
        });
    }
};
