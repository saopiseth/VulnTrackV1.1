<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE asset_inventories MODIFY COLUMN open_ports MEDIUMTEXT NULL');
    }

    public function down(): void
    {
        DB::statement('UPDATE asset_inventories SET open_ports = LEFT(open_ports, 255) WHERE open_ports IS NOT NULL');
        DB::statement('ALTER TABLE asset_inventories MODIFY COLUMN open_ports VARCHAR(255) NULL');
    }
};
