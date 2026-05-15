<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add new tracking fields
        Schema::table('vuln_tracked', function (Blueprint $table) {
            $table->unsignedSmallInteger('reopen_count')->default(0)->after('resolved_at');
            $table->unsignedSmallInteger('verification_seen_count')->default(0)->after('reopen_count');
            $table->boolean('is_false_negative')->default(false)->after('verification_seen_count');
            $table->string('status_reason', 255)->nullable()->after('is_false_negative');
        });

        // 2. Migrate existing Unresolved → Open before altering the ENUM
        DB::table('vuln_tracked')
            ->where('tracking_status', 'Unresolved')
            ->update(['tracking_status' => 'Open']);

        // 3. Extend the ENUM to include Persistent, then drop Unresolved
        //    MySQL requires a full ENUM redefinition to modify values.
        DB::statement("
            ALTER TABLE vuln_tracked
            MODIFY COLUMN tracking_status
                ENUM('New','Open','Reopened','Persistent','Resolved')
                NOT NULL DEFAULT 'New'
        ");
    }

    public function down(): void
    {
        // Reverse: restore Unresolved, remove Persistent & new fields
        DB::table('vuln_tracked')
            ->where('tracking_status', 'Persistent')
            ->update(['tracking_status' => 'Open']);

        DB::statement("
            ALTER TABLE vuln_tracked
            MODIFY COLUMN tracking_status
                ENUM('New','Open','Unresolved','Reopened','Resolved')
                NOT NULL DEFAULT 'New'
        ");

        Schema::table('vuln_tracked', function (Blueprint $table) {
            $table->dropColumn(['reopen_count', 'verification_seen_count', 'is_false_negative', 'status_reason']);
        });
    }
};
