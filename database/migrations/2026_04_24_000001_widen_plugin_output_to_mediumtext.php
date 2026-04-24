<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE vuln_findings MODIFY plugin_output MEDIUMTEXT NULL');
            DB::statement('ALTER TABLE vuln_tracked  MODIFY plugin_output MEDIUMTEXT NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE vuln_findings MODIFY plugin_output TEXT NULL');
            DB::statement('ALTER TABLE vuln_tracked  MODIFY plugin_output TEXT NULL');
        }
    }
};
