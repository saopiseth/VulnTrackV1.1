<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
