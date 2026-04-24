<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite stores enum columns as TEXT with a CHECK constraint.
        // The original migration created:
        //   check ("tracking_status" in ('New', 'Pending', 'Resolved'))
        // We must widen it to allow 'Open' and 'Reopened' before updating rows.
        if (DB::getDriverName() === 'sqlite') {
            $this->widenSqliteCheck(
                'vuln_tracked',
                "'New', 'Pending', 'Resolved'",
                "'New', 'Open', 'Unresolved', 'Reopened', 'Resolved'"
            );
            // vuln_tracked_history has CHECK constraints on prev_status and new_status
            $this->widenSqliteHistoryChecks(
                "'New', 'Pending', 'Resolved'",
                "'New', 'Open', 'Unresolved', 'Reopened', 'Resolved', 'Pending'"
            );
        }

        // ── vuln_tracked: Pending → Open ─────────────────────────────────────
        DB::table('vuln_tracked')
            ->where('tracking_status', 'Pending')
            ->update(['tracking_status' => 'Open']);

        // ── vuln_tracked_history: status columns ─────────────────────────────
        DB::table('vuln_tracked_history')
            ->where('prev_status', 'Pending')
            ->update(['prev_status' => 'Open']);

        DB::table('vuln_tracked_history')
            ->where('new_status', 'Pending')
            ->update(['new_status' => 'Open']);

        // ── MySQL only: widen enum definitions ───────────────────────────────
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE vuln_tracked MODIFY tracking_status ENUM('New','Open','Unresolved','Reopened','Resolved') NOT NULL DEFAULT 'New'");
            DB::statement("ALTER TABLE vuln_tracked_history MODIFY prev_status ENUM('New','Open','Unresolved','Reopened','Resolved','Pending') NULL");
            DB::statement("ALTER TABLE vuln_tracked_history MODIFY new_status ENUM('New','Open','Unresolved','Reopened','Resolved','Pending') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->widenSqliteCheck(
                'vuln_tracked',
                "'New', 'Open', 'Unresolved', 'Reopened', 'Resolved'",
                "'New', 'Pending', 'Resolved'"
            );
            $this->widenSqliteHistoryChecks(
                "'New', 'Open', 'Unresolved', 'Reopened', 'Resolved', 'Pending'",
                "'New', 'Pending', 'Resolved'"
            );
        }

        DB::table('vuln_tracked')
            ->where('tracking_status', 'Open')
            ->update(['tracking_status' => 'Pending']);

        DB::table('vuln_tracked_history')
            ->where('prev_status', 'Open')->update(['prev_status' => 'Pending']);

        DB::table('vuln_tracked_history')
            ->where('new_status', 'Open')->update(['new_status' => 'Pending']);
    }

    private function widenSqliteCheck(string $table, string $oldValues, string $newValues): void
    {
        DB::statement('PRAGMA writable_schema = ON');

        $row = DB::selectOne(
            "SELECT sql FROM sqlite_master WHERE type='table' AND name=?",
            [$table]
        );

        if ($row && str_contains($row->sql, $oldValues)) {
            $newSql = str_replace($oldValues, $newValues, $row->sql);
            DB::statement(
                "UPDATE sqlite_master SET sql = ? WHERE type='table' AND name=?",
                [$newSql, $table]
            );
        }

        DB::statement('PRAGMA writable_schema = OFF');
        DB::statement('PRAGMA integrity_check');
        DB::disconnect();
        DB::reconnect();
    }

    private function widenSqliteHistoryChecks(string $oldValues, string $newValues): void
    {
        DB::statement('PRAGMA writable_schema = ON');

        $row = DB::selectOne(
            "SELECT sql FROM sqlite_master WHERE type='table' AND name='vuln_tracked_history'"
        );

        if ($row) {
            $newSql = str_replace($oldValues, $newValues, $row->sql);
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
