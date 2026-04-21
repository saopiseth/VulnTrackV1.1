<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

class Agent extends Model
{
    protected $fillable = [
        'uuid',
        'hostname',
        'ip_address',
        'os',
        'status',
        'api_token',
        'last_seen',
    ];

    protected $casts = [
        'last_seen' => 'datetime',
    ];

    /** Never expose the hashed token in JSON responses */
    protected $hidden = ['api_token'];

    // ── Relationships ──────────────────────────────────────────────

    /**
     * Full hardware history for this agent (append-only snapshots).
     */
    public function hardwareSnapshots(): HasMany
    {
        return $this->hasMany(AgentHardwareSnapshot::class);
    }

    /**
     * Convenience accessor: most recent hardware snapshot.
     */
    public function latestSnapshot(): HasOne
    {
        return $this->hasOne(AgentHardwareSnapshot::class)
                    ->latestOfMany('collected_at');
    }

    /**
     * Current software inventory (sync-model — always reflects last scan).
     */
    public function software(): HasMany
    {
        return $this->hasMany(InstalledSoftware::class);
    }

    /**
     * Audit/event log for this agent.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(AgentLog::class)->latest('created_at');
    }

    // ── Status helpers ─────────────────────────────────────────────

    public function markOnline(): void
    {
        $this->update(['status' => 'online', 'last_seen' => now()]);
    }

    public function markOffline(): void
    {
        $this->update(['status' => 'offline']);
    }

    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    // ── Query scopes ───────────────────────────────────────────────

    /**
     * Agents that haven't sent a heartbeat in $minutes — candidates for
     * a scheduled offline-detection job.
     */
    public function scopeStale(Builder $query, int $minutes = 15): Builder
    {
        return $query->where('last_seen', '<', now()->subMinutes($minutes))
                     ->where('status', 'online');
    }

    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('status', 'online');
    }

    public function scopeOffline(Builder $query): Builder
    {
        return $query->where('status', 'offline');
    }

    // ── Future: vulnerability surface ─────────────────────────────

    /**
     * Returns all unique (name, version) pairs installed on this agent,
     * ready to be joined with a CVE/NVD lookup table.
     *
     * Usage: $agent->vulnerableSoftwarePairs()
     */
    public function vulnerableSoftwarePairs(): \Illuminate\Support\Collection
    {
        return $this->software()
                    ->select('name', 'version')
                    ->orderBy('name')
                    ->get();
    }
}
