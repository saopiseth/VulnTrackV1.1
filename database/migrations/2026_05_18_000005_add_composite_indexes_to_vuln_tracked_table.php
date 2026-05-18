<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vuln_tracked', function (Blueprint $table) {
            $table->index(['assessment_id', 'tracking_status'], 'vt_aid_status');
            $table->index(['assessment_id', 'severity'],        'vt_aid_severity');
            $table->index(['assessment_id', 'ip_address'],      'vt_aid_ip');
            $table->index(['assessment_id', 'hostname'],        'vt_aid_hostname');
            $table->index(['assessment_id', 'os_family'],       'vt_aid_os_family');
            $table->index(['assessment_id', 'plugin_id'],       'vt_aid_plugin');
            $table->index(['assessment_id', 'vuln_key'],        'vt_aid_vuln_key');
        });
    }

    public function down(): void
    {
        Schema::table('vuln_tracked', function (Blueprint $table) {
            $table->dropIndex('vt_aid_status');
            $table->dropIndex('vt_aid_severity');
            $table->dropIndex('vt_aid_ip');
            $table->dropIndex('vt_aid_hostname');
            $table->dropIndex('vt_aid_os_family');
            $table->dropIndex('vt_aid_plugin');
            $table->dropIndex('vt_aid_vuln_key');
        });
    }
};
