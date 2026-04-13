<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vuln_findings', function (Blueprint $table) {
            $table->string('os_name')->nullable()->after('os_detected');       // e.g. "Ubuntu 22.04"
            $table->string('os_family')->nullable()->after('os_name');         // Windows / Linux / Unix / Other
            $table->unsignedTinyInteger('os_confidence')->default(0)->after('os_family'); // 0–100%
        });
    }

    public function down(): void
    {
        Schema::table('vuln_findings', function (Blueprint $table) {
            $table->dropColumn(['os_name', 'os_family', 'os_confidence']);
        });
    }
};
