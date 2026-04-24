<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vuln_scans', function (Blueprint $table) {
            $table->string('upload_status')->default('completed')->after('notes');
            $table->text('upload_error')->nullable()->after('upload_status');
            $table->string('file_path')->nullable()->after('upload_error');
        });
    }

    public function down(): void
    {
        Schema::table('vuln_scans', function (Blueprint $table) {
            $table->dropColumn(['upload_status', 'upload_error', 'file_path']);
        });
    }
};
