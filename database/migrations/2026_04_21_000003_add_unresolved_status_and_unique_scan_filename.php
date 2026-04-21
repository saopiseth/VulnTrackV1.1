<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Two related changes:
 *
 * 1. Add 'Unresolved' to the tracking_status enum on vuln_tracked and the
 *    prev_status / new_status enums on vuln_tracked_history.
 *
 *    'Open'       = finding was created on the first (baseline) scan
 *    'Unresolved' = finding was confirmed still present on a subsequent scan
 *    'New'        = finding appeared for the first time on a subsequent scan
 *    'Reopened'   = finding reappeared after being Resolved
 *    'Resolved'   = finding was absent from the last scan of its host
 *
 * 2. Add UNIQUE(assessment_id, filename) to vuln_scans so the database itself
 *    enforces that the same file cannot be uploaded twice to the same assessment.
 *    Duplicate rows are collapsed before the constraint is applied.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Widen status CHECK constraints to include 'Unresolved' ─────────
        if (DB::getDriverName() === 'sqlite') {
            $this->widenSqliteCheck(
                'vuln_tracked',
                "'New', 'Open', 'Reopened', 'Resolved'",
                "'New', 'Open', 'Unresolved', 'Reopened', 'Resolved'"
            );
            $this->widenSqliteHistoryStatuses(
                "'New', 'Open', 'Reopened', 'Resolved'",
                "'New', 'Open', 'Unresolved', 'Reopened', 'Resolved'"
            );
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE vuln_tracked MODIFY tracking_status
                ENUM('New','Open','Unresolved','Reopened','Resolved') NOT NULL DEFAULT 'New'");
            DB::statement("ALTER TABLE vuln_tracked_history
                MODIFY prev_status ENUM('New','Open','Unresolved','Reopened','Resolved') NULL,
                MODIFY new_status  ENUM('New','Open','Unresolved','Reopened','Resolved') NULL");
        }

        // ── 2. Remove duplicate scan rows (keep lowest id per assessment+filename)
        //       Must happen BEFORE adding the unique constraint.
        // ─────────────────────────────────────────────────────────────────────
        // Find the canonical (first) scan id for every (assessment_id, filename) pair.
        $keep = DB::table('vuln_scans')
            ->selectRaw('MIN(id) as keep_id')
            ->groupBy('assessment_id', 'filename')
            ->pluck('keep_id');

        $duplicateScanIds = DB::table('vuln_scans')
            ->whereNotIn('id', $keep)
            ->pluck('id');

        if ($duplicateScanIds->isNotEmpty()) {
            // Find which assessments are affected by the duplicate scans.
            $affectedAssessments = DB::table('vuln_scans')
                ->whereIn('id', $duplicateScanIds)
                ->distinct()
                ->pluck('assessment_id');

            // Wipe ALL tracking data for affected assessments.
            // vuln_tracked.first_scan_id / last_scan_id reference vuln_scans, so we
            // must remove those rows before deleting the scans (no cascade on those FKs).
            // Deleting vuln_tracked cascades to vuln_tracked_history automatically.
            $trackedIds = DB::table('vuln_tracked')
                ->whereIn('assessment_id', $affectedAssessments)
                ->pluck('id');

            if ($trackedIds->isNotEmpty()) {
                DB::table('vuln_tracked_history')
                    ->whereIn('tracked_id', $trackedIds)
                    ->delete();
                DB::table('vuln_tracked')
                    ->whereIn('id', $trackedIds)
                    ->delete();
            }

            // Now safe to remove the duplicate scan rows and their findings.
            DB::table('vuln_findings')
                ->whereIn('scan_id', $duplicateScanIds)
                ->delete();

            DB::table('vuln_scans')
                ->whereIn('id', $duplicateScanIds)
                ->delete();
        }

        // ── 3. Add UNIQUE constraint on (assessment_id, filename) ────────────
        Schema::table('vuln_scans', function (Blueprint $table) {
            $table->unique(['assessment_id', 'filename'], 'uq_scan_assessment_filename');
        });
    }

    public function down(): void
    {
        Schema::table('vuln_scans', function (Blueprint $table) {
            $table->dropUnique('uq_scan_assessment_filename');
        });

        if (DB::getDriverName() === 'sqlite') {
            $this->widenSqliteCheck(
                'vuln_tracked',
                "'New', 'Open', 'Unresolved', 'Reopened', 'Resolved'",
                "'New', 'Open', 'Reopened', 'Resolved'"
            );
            $this->widenSqliteHistoryStatuses(
                "'New', 'Open', 'Unresolved', 'Reopened', 'Resolved'",
                "'New', 'Open', 'Reopened', 'Resolved'"
            );
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE vuln_tracked MODIFY tracking_status
                ENUM('New','Open','Reopened','Resolved') NOT NULL DEFAULT 'New'");
            DB::statement("ALTER TABLE vuln_tracked_history
                MODIFY prev_status ENUM('New','Open','Reopened','Resolved') NULL,
                MODIFY new_status  ENUM('New','Open','Reopened','Resolved') NULL");
        }
    }

    // ── SQLite helpers ────────────────────────────────────────────────────────

    private function widenSqliteCheck(string $table, string $from, string $to): void
    {
        DB::statement('PRAGMA writable_schema = ON');

        $row = DB::selectOne(
            "SELECT sql FROM sqlite_master WHERE type='table' AND name=?",
            [$table]
        );

        if ($row && str_contains($row->sql, $from)) {
            DB::statement(
                "UPDATE sqlite_master SET sql = ? WHERE type='table' AND name=?",
                [str_replace($from, $to, $row->sql), $table]
            );
        }

        DB::statement('PRAGMA writable_schema = OFF');
        DB::statement('PRAGMA integrity_check');
        DB::disconnect();
        DB::reconnect();
    }

    private function widenSqliteHistoryStatuses(string $from, string $to): void
    {
        DB::statement('PRAGMA writable_schema = ON');

        $row = DB::selectOne(
            "SELECT sql FROM sqlite_master WHERE type='table' AND name='vuln_tracked_history'"
        );

        if ($row) {
            $newSql = str_replace($from, $to, $row->sql);
            DB::statement(
                "UPDATE sqlite_master SET sql = ? WHERE type='table' AND name='vuln_tracked_history'",
                [$newSql]
            );
        }

        DB::statement('PRAGMA writable_schema = OFF');
        DB::statement('PRAGMA integrity_check');
        DB::disconnect();
        DB::reconnect();
    }
};
