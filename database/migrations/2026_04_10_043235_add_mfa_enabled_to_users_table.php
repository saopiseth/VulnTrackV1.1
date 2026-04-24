<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'mfa_enabled')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('mfa_enabled')->default(true)->after('role');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'mfa_enabled')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('mfa_enabled');
            });
        }
    }
};
