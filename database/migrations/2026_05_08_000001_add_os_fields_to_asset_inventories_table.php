<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_inventories', function (Blueprint $table) {
            $table->string('os_family')->nullable()->after('os');
            $table->string('os_kernel')->nullable()->after('os_family');

            // Promote the plain index to UNIQUE so upsert (ON DUPLICATE KEY) works correctly.
            $table->dropIndex('asset_inventories_ip_address_index');
            $table->unique('ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('asset_inventories', function (Blueprint $table) {
            $table->dropUnique('asset_inventories_ip_address_unique');
            $table->index('ip_address');
            $table->dropColumn(['os_family', 'os_kernel']);
        });
    }
};
