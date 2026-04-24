<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('vuln_tracked')->whereNull('port')->update(['port' => '']);
        DB::table('vuln_findings')->whereNull('port')->update(['port' => '']);

        $dupes = DB::table('vuln_tracked as a')
            ->join('vuln_tracked as b', function ($j) {
                $j->on('a.assessment_id', '=', 'b.assessment_id')
                  ->on('a.ip_address',    '=', 'b.ip_address')
                  ->on('a.plugin_id',     '=', 'b.plugin_id')
                  ->on('a.port',          '=', 'b.port')
                  ->whereColumn('a.id', '<', 'b.id');
            })
            ->pluck('a.id');

        if ($dupes->isNotEmpty()) {
            DB::table('vuln_tracked')->whereIn('id', $dupes)->delete();
        }

        if (Schema::hasIndex('vuln_tracked', 'uq_tracked_key')) {
            Schema::table('vuln_tracked', function (Blueprint $table) {
                $table->dropUnique('uq_tracked_key');
            });
        }

        if (!Schema::hasIndex('vuln_tracked', 'uq_tracked_key')) {
            Schema::table('vuln_tracked', function (Blueprint $table) {
                $table->unique(
                    ['assessment_id', 'ip_address', 'plugin_id', 'port'],
                    'uq_tracked_key'
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('vuln_tracked', 'uq_tracked_key')) {
            Schema::table('vuln_tracked', function (Blueprint $table) {
                $table->dropUnique('uq_tracked_key');
            });
        }

        Schema::table('vuln_tracked', function (Blueprint $table) {
            $table->unique(
                ['assessment_id', 'ip_address', 'plugin_id'],
                'uq_tracked_key'
            );
        });
    }
};
