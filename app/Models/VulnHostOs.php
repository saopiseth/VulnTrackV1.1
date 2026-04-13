<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VulnHostOs extends Model
{
    protected $table = 'vuln_host_os';

    protected $fillable = [
        'assessment_id', 'scan_id', 'ip_address', 'hostname',
        'os_name', 'os_family', 'os_confidence', 'detection_sources',
        'os_override', 'os_override_family', 'os_override_by', 'os_override_at', 'os_override_note',
        'os_history',
    ];

    protected $casts = [
        'detection_sources' => 'array',
        'os_history'        => 'array',
        'os_override_at'    => 'datetime',
    ];

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
