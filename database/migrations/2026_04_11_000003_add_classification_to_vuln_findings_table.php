<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vuln_findings', function (Blueprint $table) {
            $table->string('vuln_category')->nullable()->after('os_detected');      // OS / Application / Database / Web Server / Network / SSL-TLS / Policy / Other
            $table->string('affected_component')->nullable()->after('vuln_category'); // e.g. "Apache HTTP Server", "OpenSSL 1.1.1", "Windows SMB"
        });
    }

    public function down(): void
    {
        Schema::table('vuln_findings', function (Blueprint $table) {
            $table->dropColumn(['vuln_category', 'affected_component']);
        });
    }
};
