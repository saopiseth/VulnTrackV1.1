<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'dashboard_layout')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('dashboard_layout')->nullable()->after('mfa_enabled');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'dashboard_layout')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('dashboard_layout');
            });
        }
    }
};
