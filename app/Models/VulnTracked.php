<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VulnTracked extends Model
{
    protected $table = 'vuln_tracked';

    protected $fillable = [
        'assessment_id',
        'ip_address', 'hostname',
        'plugin_id', 'cve',
        'vuln_name', 'description', 'remediation_text',
        'severity', 'port', 'protocol',
        'vuln_category', 'affected_component',
        'os_detected', 'os_name', 'os_family',
        'tracking_status',
        'first_seen_at', 'last_seen_at', 'resolved_at',
        'first_scan_id', 'last_scan_id',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at'  => 'datetime',
        'resolved_at'   => 'datetime',
    ];

    public static function statuses(): array
    {
        return ['New', 'Pending', 'Resolved'];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(VulnAssessment::class, 'assessment_id');
    }

    public function firstScan(): BelongsTo
    {
        return $this->belongsTo(VulnScan::class, 'first_scan_id');
    }

    public function lastScan(): BelongsTo
    {
        return $this->belongsTo(VulnScan::class, 'last_scan_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(VulnTrackedHistory::class, 'tracked_id')->orderBy('changed_at');
    }

    public function remediation(): BelongsTo
    {
        return $this->belongsTo(VulnRemediation::class, 'assessment_id', 'assessment_id')
            ->where('plugin_id', $this->plugin_id)
            ->where('ip_address', $this->ip_address);
    }

    /** Returns [bg, color, icon] for tracking_status badge */
    public static function statusStyle(string $status): array
    {
        return match ($status) {
            'New'      => ['#fee2e2', '#991b1b', 'bi-asterisk'],
            'Pending'  => ['#fef9c3', '#854d0e', 'bi-arrow-repeat'],
            'Resolved' => ['#d1fae5', '#065f46', 'bi-check-circle-fill'],
            default    => ['#f1f5f9', '#475569', 'bi-question-circle'],
        };
    }
}
