<?php

namespace App\Models;

use App\Models\User;
use App\Services\VulnTrackingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VulnTracked extends Model
{
    protected $table = 'vuln_tracked';

    protected $fillable = [
        'assessment_id',
        'ip_address', 'hostname',
        'plugin_id', 'cve', 'cvss_score',
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
        'cvss_score'    => 'float',
    ];

    // ── Status constants ──────────────────────────────────────────────────────

    const STATUS_NEW        = 'New';
    const STATUS_OPEN       = 'Open';        // baseline — first scan only
    const STATUS_UNRESOLVED = 'Unresolved';  // confirmed present in a subsequent scan
    const STATUS_REOPENED   = 'Reopened';    // was Resolved, reappeared
    const STATUS_RESOLVED   = 'Resolved';

    /** All valid tracking_status values. */
    public static function statuses(): array
    {
        return [
            self::STATUS_NEW,
            self::STATUS_OPEN,
            self::STATUS_UNRESOLVED,
            self::STATUS_REOPENED,
            self::STATUS_RESOLVED,
        ];
    }

    /** Statuses that are semantically "open" (active / unresolved). */
    public static function openStatuses(): array
    {
        return VulnTrackingService::OPEN_STATUSES;
    }

    /** Returns true when this finding is still active. */
    public function isOpen(): bool
    {
        return in_array($this->tracking_status, self::openStatuses(), true);
    }

    /** Returns true when this finding has been resolved (closed). */
    public function isResolved(): bool
    {
        return $this->tracking_status === self::STATUS_RESOLVED;
    }

    /** Returns [bg, color, icon] for the tracking_status badge in views. */
    public static function statusStyle(string $status): array
    {
        return match ($status) {
            self::STATUS_NEW        => ['#fee2e2', '#991b1b', 'bi-plus-circle-fill'],
            self::STATUS_OPEN       => ['#fef9c3', '#854d0e', 'bi-shield-exclamation'],
            self::STATUS_UNRESOLVED => ['#fef3c7', '#92400e', 'bi-arrow-repeat'],
            self::STATUS_REOPENED   => ['#ffedd5', '#c2410c', 'bi-arrow-counterclockwise'],
            self::STATUS_RESOLVED   => ['#d1fae5', '#065f46', 'bi-check-circle-fill'],
            'Pending'               => ['#fef9c3', '#854d0e', 'bi-arrow-repeat'], // legacy
            default                 => ['#f1f5f9', '#475569', 'bi-question-circle'],
        };
    }

    // ── Visibility scope ─────────────────────────────────────────────────────
    // Single source of truth for group-based access control.
    // Admins and Assessors see all findings; Patch Administrators see only
    // findings whose remediation is assigned to one of their groups.
    // Usage: VulnTracked::visibleTo(Auth::user())->where(...)
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->isAdministrator() || $user->isAssessor()) {
            return;
        }

        // loadMissing ensures the relation is only queried once per request
        // even when this scope is applied across multiple chained queries.
        $user->loadMissing('groups');
        $groupIds = $user->groups->pluck('id');

        if ($groupIds->isEmpty()) {
            $query->whereRaw('0 = 1');
            return;
        }

        $query->whereExists(function ($sub) use ($groupIds) {
            $sub->selectRaw('1')
                ->from('vuln_remediations')
                ->whereColumn('vuln_remediations.plugin_id',     'vuln_tracked.plugin_id')
                ->whereColumn('vuln_remediations.ip_address',    'vuln_tracked.ip_address')
                ->whereColumn('vuln_remediations.assessment_id', 'vuln_tracked.assessment_id')
                ->whereIn('vuln_remediations.assigned_group_id', $groupIds);
        });
    }

    // ── Relationships ─────────────────────────────────────────────────────────

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
}
