<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fixes the vuln_tracked unique key to include port.
 *
 * Before: UNIQUE(assessment_id, ip_address, plugin_id)
 * After:  UNIQUE(assessment_id, ip_address, plugin_id, port)
 *
 * Without port, two findings for the same plugin on different ports
 * (e.g. SSL/TLS on 443 AND 8443) would be collapsed into a single row,
 * silently discarding one of them.
 *
 * Port is normalised to '' (empty string) so NULL ports don't create
 * phantom uniqueness gaps across databases.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Normalise NULL ports to '' ─────────────────────────────────────
        DB::table('vuln_tracked')->whereNull('port')->update(['port' => '']);
        DB::table('vuln_findings')->whereNull('port')->update(['port' => '']);

        // ── 2. Before widening the unique key, collapse any existing duplicate
        //       rows that share the same (assessment_id, ip_address, plugin_id)
        //       but now differ only by port = ''. Keep the row with the highest
        //       id (most recent); delete the rest.
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

        // ── 3. Drop old unique index ──────────────────────────────────────────
        Schema::table('vuln_tracked', function (Blueprint $table) {
            $table->dropUnique('uq_tracked_key');
        });

        // ── 4. Add new unique index that includes port ────────────────────────
        Schema::table('vuln_tracked', function (Blueprint $table) {
            $table->unique(
                ['assessment_id', 'ip_address', 'plugin_id', 'port'],
                'uq_tracked_key'
            );
        });
    }

    public function down(): void
    {
        Schema::table('vuln_tracked', function (Blueprint $table) {
            $table->dropUnique('uq_tracked_key');
        });

        Schema::table('vuln_tracked', function (Blueprint $table) {
            $table->unique(
                ['assessment_id', 'ip_address', 'plugin_id'],
                'uq_tracked_key'
            );
        });
    }
};
