<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SlaPolicy extends Model
{
    protected $fillable = [
        'name', 'description',
        'critical_days', 'high_days', 'medium_days', 'low_days',
        'is_default', 'created_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(VulnAssessment::class, 'sla_policy_id');
    }

    public function daysForSeverity(string $severity): ?int
    {
        return match ($severity) {
            'Critical' => $this->critical_days,
            'High'     => $this->high_days,
            'Medium'   => $this->medium_days,
            'Low'      => $this->low_days,
            default    => null,
        };
    }

    /** Returns [status, label, bg, color] for a finding given its first_seen_at and severity. */
    public function slaStatus(string $severity, \Carbon\Carbon $firstSeen, bool $resolved = false, ?\Carbon\Carbon $resolvedAt = null): array
    {
        $days = $this->daysForSeverity($severity);
        if ($days === null) {
            return ['na', 'N/A', '#f1f5f9', '#94a3b8'];
        }

        $deadline = $firstSeen->copy()->addDays($days);

        if ($resolved) {
            $metDate = $resolvedAt ?? now();
            return $metDate->lte($deadline)
                ? ['met',     'SLA Met',     '#d1fae5', '#065f46']
                : ['breached','SLA Breached','#fee2e2', '#991b1b'];
        }

        $now = now();
        if ($now->gt($deadline)) {
            return ['breached', 'Breached', '#fee2e2', '#991b1b'];
        }
        if ($now->gt($deadline->copy()->subDays(7))) {
            return ['approaching', 'Approaching', '#fef9c3', '#854d0e'];
        }
        return ['on-track', 'On Track', '#d1fae5', '#065f46'];
    }
}
