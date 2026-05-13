<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Convert ENUM to VARCHAR so 'Not Found in Scan' and future values work
        DB::statement("ALTER TABLE asset_inventories MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Active'");
    }

    public function down(): void
    {
        DB::statement("UPDATE asset_inventories SET status = 'Inactive' WHERE status NOT IN ('Active','Inactive','Decommissioned')");
        DB::statement("ALTER TABLE asset_inventories MODIFY COLUMN status ENUM('Active','Inactive','Decommissioned') NOT NULL DEFAULT 'Active'");
    }
};
