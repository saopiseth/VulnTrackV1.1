<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vuln_host_os', function (Blueprint $table) {
            $table->string('identified_scope', 50)->nullable()
                  ->after('system_owner')
                  ->comment('PCI | DMZ | Internal | External | Third-Party');

            $table->string('environment', 20)->nullable()
                  ->after('identified_scope')
                  ->comment('PROD | UAT | STAGE');

            $table->string('location', 255)->nullable()
                  ->after('environment')
                  ->comment('DC | DR | Cloud');
        });
    }

    public function down(): void
    {
        Schema::table('vuln_host_os', function (Blueprint $table) {
            $table->dropColumn(['identified_scope', 'environment', 'location']);
        });
    }
};
