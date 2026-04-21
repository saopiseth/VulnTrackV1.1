<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VulnHostOs extends Model
{
    protected $table = 'vuln_host_os';

    protected $fillable = [
        'assessment_id', 'scan_id', 'ip_address', 'hostname',
        'os_name', 'os_family', 'os_confidence', 'os_kernel', 'detection_sources',
        'asset_criticality', 'criticality_set_by', 'criticality_set_at',
        'system_name', 'system_owner',
        'identified_scope', 'environment', 'location',
        'os_override', 'os_override_family', 'os_override_by', 'os_override_at', 'os_override_note',
        'os_history',
    ];

    protected $casts = [
        'detection_sources'  => 'array',
        'os_history'         => 'array',
        'os_override_at'     => 'datetime',
        'criticality_set_at' => 'datetime',
        'asset_criticality'  => 'integer',
    ];

    public static function scopeOptions(): array
    {
        return ['PCI', 'DMZ', 'Internal', 'External', 'Third-Party'];
    }

    public static function environmentOptions(): array
    {
        return ['PROD', 'UAT', 'STAGE'];
    }

    public static function locationOptions(): array
    {
        return ['DC', 'DR', 'Cloud'];
    }

    /** Returns display metadata for each criticality level. */
    public static function criticalityLevels(): array
    {
        return [
            1 => ['label' => 'Mission-Critical',    'desc' => 'Core banking, payment systems',  'bg' => '#fee2e2', 'color' => '#991b1b', 'icon' => 'bi-shield-fill-exclamation'],
            2 => ['label' => 'Business-Critical',   'desc' => 'Customer services, APIs',        'bg' => '#fef3c7', 'color' => '#92400e', 'icon' => 'bi-exclamation-triangle-fill'],
            3 => ['label' => 'Business Operational','desc' => 'Internal tools',                 'bg' => '#dbeafe', 'color' => '#1e40af', 'icon' => 'bi-gear-fill'],
            4 => ['label' => 'Administrative',      'desc' => 'Support systems',                'bg' => '#f3f4f6', 'color' => '#374151', 'icon' => 'bi-person-gear'],
            5 => ['label' => 'None-Bank',            'desc' => '3rd party / non-banking',       'bg' => '#f0fdf4', 'color' => '#166534', 'icon' => 'bi-building'],
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(VulnAssessment::class, 'assessment_id');
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(VulnScan::class, 'scan_id');
    }

    public function overrideBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'os_override_by');
    }

    /** Effective OS name (override wins) */
    public function effectiveOsName(): ?string
    {
        return $this->os_override ?? $this->os_name;
    }

    /** Effective OS family (override wins) */
    public function effectiveOsFamily(): ?string
    {
        return $this->os_override_family ?? $this->os_family;
    }

    /** Whether a manual override is active */
    public function hasOverride(): bool
    {
        return !is_null($this->os_override);
    }
}
