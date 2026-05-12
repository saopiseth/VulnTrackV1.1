<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class AssetInventory extends Model
{
    protected $table = 'asset_inventories';

    protected $fillable = [
        'ip_address', 'hostname', 'identified_scope', 'environment',
        'system_name', 'system_owner', 'remediation_sla',
        'classification_level', 'critical_level',
        'os', 'os_family', 'os_kernel', 'open_ports',
        'vuln_critical', 'vuln_high', 'vuln_medium', 'vuln_low',
        'tags', 'notes', 'status', 'last_scanned_at', 'created_by',
    ];

    protected $casts = [
        'last_scanned_at'      => 'datetime',
        'classification_level' => 'integer',
        'vuln_critical'        => 'integer',
        'vuln_high'            => 'integer',
        'vuln_medium'          => 'integer',
        'vuln_low'             => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function totalVulns(): int
    {
        return $this->vuln_critical + $this->vuln_high + $this->vuln_medium + $this->vuln_low;
    }

    public function openPortsArray(): array
    {
        return $this->open_ports
            ? array_filter(array_map('trim', explode(',', $this->open_ports)))
            : [];
    }

    /**
     * Sync business fields from assessment_scopes into asset_inventories (non-destructive),
     * and insert scope IPs that have no inventory record yet as 'Not Found in Latest Scan'.
     */
    public static function syncFromScopes(): void
    {
        // Update existing records by IP match — scope wins only when it has a non-empty value
        DB::statement("
            UPDATE asset_inventories ai
            JOIN (
                SELECT ip_address,
                    MAX(system_name)      AS system_name,
                    MAX(identified_scope) AS identified_scope,
                    MAX(environment)      AS environment,
                    MAX(system_owner)     AS system_owner,
                    MAX(remediation_sla)  AS remediation_sla
                FROM assessment_scopes
                WHERE ip_address IS NOT NULL AND ip_address != ''
                GROUP BY ip_address
            ) sc ON sc.ip_address = ai.ip_address
            SET
                ai.system_name      = COALESCE(NULLIF(sc.system_name,      ''), ai.system_name),
                ai.identified_scope = COALESCE(NULLIF(sc.identified_scope, ''), ai.identified_scope),
                ai.environment      = COALESCE(NULLIF(sc.environment,      ''), ai.environment),
                ai.system_owner     = COALESCE(NULLIF(sc.system_owner,     ''), ai.system_owner),
                ai.remediation_sla  = COALESCE(NULLIF(sc.remediation_sla,  ''), ai.remediation_sla),
                ai.updated_at       = NOW()
        ");

        // Insert scope IPs not yet tracked in the inventory as 'Not Found in Latest Scan'
        DB::statement("
            INSERT IGNORE INTO asset_inventories
                (ip_address, system_name, identified_scope, environment,
                 system_owner, remediation_sla, status, created_at, updated_at)
            SELECT
                sc.ip_address,
                MAX(sc.system_name),
                MAX(sc.identified_scope),
                MAX(sc.environment),
                MAX(sc.system_owner),
                MAX(sc.remediation_sla),
                'Not Found in Latest Scan',
                NOW(),
                NOW()
            FROM assessment_scopes sc
            LEFT JOIN asset_inventories ai ON ai.ip_address = sc.ip_address
            WHERE sc.ip_address IS NOT NULL AND sc.ip_address != ''
              AND ai.id IS NULL
            GROUP BY sc.ip_address
        ");
    }

    /**
     * After a new scan is processed, mark every inventory record as
     * 'Not Found in Latest Scan', then flip the scanned IPs back to 'Active'.
     * Records manually set to Inactive or Decommissioned are left unchanged.
     */
    public static function applyLatestScanStatus(array $scannedIps): void
    {
        if (empty($scannedIps)) {
            return;
        }

        DB::table('asset_inventories')
            ->whereIn('status', ['Active', 'Not Found in Latest Scan'])
            ->update(['status' => 'Not Found in Latest Scan', 'updated_at' => now()]);

        DB::table('asset_inventories')
            ->whereIn('ip_address', $scannedIps)
            ->update(['status' => 'Active', 'updated_at' => now()]);
    }

    /** Returns badge style metadata for the detected OS family. */
    public function osFamilyBadge(): array
    {
        return match (strtolower($this->os_family ?? '')) {
            'windows'         => ['bg' => '#dbeafe', 'color' => '#1e40af', 'icon' => 'bi-windows'],
            'linux'           => ['bg' => '#dcfce7', 'color' => '#166534', 'icon' => 'bi-terminal-fill'],
            'macos', 'darwin' => ['bg' => '#f3e8ff', 'color' => '#6b21a8', 'icon' => 'bi-apple'],
            'network'         => ['bg' => '#fef3c7', 'color' => '#92400e', 'icon' => 'bi-router-fill'],
            default           => ['bg' => '#f1f5f9', 'color' => '#475569', 'icon' => 'bi-hdd-fill'],
        };
    }
}
