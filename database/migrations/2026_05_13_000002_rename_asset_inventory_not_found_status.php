<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('asset_inventories')
            ->where('status', 'Not Found in Latest Scan')
            ->update(['status' => 'Not Found in Scan']);

        $latestScanDate = DB::table('asset_inventories')
            ->whereNotNull('last_scanned_at')
            ->selectRaw('MAX(DATE(last_scanned_at)) as latest_scan_date')
            ->value('latest_scan_date');

        if (!$latestScanDate) {
            return;
        }

        DB::table('asset_inventories')
            ->where('status', '!=', 'Decommissioned')
            ->where(fn ($query) => $query
                ->whereNull('last_scanned_at')
                ->orWhereRaw('DATE(last_scanned_at) < ?', [$latestScanDate]))
            ->update(['status' => 'Not Found in Scan']);

        DB::table('asset_inventories')
            ->where('status', '!=', 'Decommissioned')
            ->whereRaw('DATE(last_scanned_at) = ?', [$latestScanDate])
            ->update(['status' => 'Active']);
    }

    public function down(): void
    {
        DB::table('asset_inventories')
            ->where('status', 'Not Found in Scan')
            ->update(['status' => 'Not Found in Latest Scan']);
    }
};
