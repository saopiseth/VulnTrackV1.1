<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('administrator','assessor','patch_administrator') NOT NULL DEFAULT 'assessor'");
    }

    public function down(): void
    {
        // Demote any patch_administrator users before removing the enum value
        DB::table('users')->where('role', 'patch_administrator')->update(['role' => 'assessor']);
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('administrator','assessor') NOT NULL DEFAULT 'assessor'");
    }
};
