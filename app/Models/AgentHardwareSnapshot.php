<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class AgentHardwareSnapshot extends Model
{
    protected $table = 'agent_hardware_snapshots';

    protected $fillable = [
        'agent_id',
        'cpu',
        'ram',
        'disk',
        'os_version',
        'collected_at',
    ];

    protected $casts = [
        'ram'          => 'integer',
        'disk'         => 'integer',
        'collected_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    // ── Accessors ──────────────────────────────────────────────────

    /**
     * Human-readable RAM string.
     * Examples: "16.0 GB", "512 MB"
     */
    public function getRamFormattedAttribute(): string
    {
        if ($this->ram === null) {
            return '—';
        }

        return $this->ram >= 1024
            ? round($this->ram / 1024, 1) . ' GB'
            : $this->ram . ' MB';
    }

    /**
     * Human-readable disk string.
     * Examples: "1.0 TB", "256 GB"
     */
    public function getDiskFormattedAttribute(): string
    {
        if ($this->disk === null) {
            return '—';
        }

        return $this->disk >= 1024
            ? round($this->disk / 1024, 1) . ' TB'
            : $this->disk . ' GB';
    }

    // ── Scopes ────────────────────────────────────────────────────

    /**
     * Retrieve snapshots collected within a date range.
     * Useful for asset-change-tracking reports.
     */
    public function scopeBetween(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('collected_at', [$from, $to]);
    }

    /**
     * Compare two consecutive snapshots and return a diff array.
     * Returns empty array if no differences.
     *
     * @return array{field: string, before: mixed, after: mixed}[]
     */
    public static function diff(self $before, self $after): array
    {
        $changes = [];

        foreach (['cpu', 'ram', 'disk', 'os_version'] as $field) {
            if ($before->{$field} !== $after->{$field}) {
                $changes[] = [
                    'field'  => $field,
                    'before' => $before->{$field},
                    'after'  => $after->{$field},
                ];
            }
        }

        return $changes;
    }
}
