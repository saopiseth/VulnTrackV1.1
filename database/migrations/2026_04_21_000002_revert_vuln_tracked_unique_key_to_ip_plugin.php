<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Corrects the tracking identity to (assessment_id, ip_address, plugin_id).
 *
 * Port is NOT part of the vulnerability identity per the assessment spec:
 *   "A vulnerability is uniquely identified by: IP Address + Plugin ID"
 *
 * If the same plugin fired on multiple ports (e.g. SSL on 443 and 8443)
 * those are the SAME vulnerability on the same host — not two separate issues.
 * We keep the row with the highest id (most recently updated) and delete extras.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Collapse rows that share (assessment_id, ip_address, plugin_id)
        //       but differ by port — keep the row with the highest id.
        $dupes = DB::table('vuln_tracked as a')
            ->join('vuln_tracked as b', function ($j) {
                $j->on('a.assessment_id', '=', 'b.assessment_id')
                  ->on('a.ip_address',    '=', 'b.ip_address')
                  ->on('a.plugin_id',     '=', 'b.plugin_id')
                  ->whereColumn('a.id', '<', 'b.id');
            })
            ->pluck('a.id');

        if ($dupes->isNotEmpty()) {
            DB::table('vuln_tracked_history')
                ->whereIn('tracked_id', $dupes)
                ->delete();
            DB::table('vuln_tracked')
                ->whereIn('id', $dupes)
                ->delete();
        }

        // ── 2. Drop the port-inclusive unique index ───────────────────────────
        Schema::table('vuln_tracked', function (Blueprint $table) {
            $table->dropUnique('uq_tracked_key');
        });

        // ── 3. Restore unique index WITHOUT port ──────────────────────────────
        Schema::table('vuln_tracked', function (Blueprint $table) {
            $table->unique(
                ['assessment_id', 'ip_address', 'plugin_id'],
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
                ['assessment_id', 'ip_address', 'plugin_id', 'port'],
                'uq_tracked_key'
            );
        });
    }
};
