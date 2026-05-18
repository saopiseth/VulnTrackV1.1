<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_scopes', function (Blueprint $table) {
            // Covers the scope subquery: WHERE group_id = ? AND ip_address IS NOT NULL
            $table->index(['group_id', 'ip_address'], 'idx_as_group_ip');
        });
    }

    public function down(): void
    {
        Schema::table('assessment_scopes', function (Blueprint $table) {
            $table->dropIndex('idx_as_group_ip');
        });
    }
};
