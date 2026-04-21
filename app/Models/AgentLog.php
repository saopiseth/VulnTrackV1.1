<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class AgentLog extends Model
{
    /**
     * Logs are immutable — no updated_at column in this table.
     */
    public const UPDATED_AT = null;

    // ── Event type constants ───────────────────────────────────────

    public const EVENT_REGISTER  = 'register';
    public const EVENT_HEARTBEAT = 'heartbeat';
    public const EVENT_UPDATE    = 'update';
    public const EVENT_ERROR     = 'error';

    protected $fillable = [
        'agent_id',
        'event_type',
        'message',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeErrors(Builder $query): Builder
    {
        return $query->where('event_type', self::EVENT_ERROR);
    }

    public function scopeHeartbeats(Builder $query): Builder
    {
        return $query->where('event_type', self::EVENT_HEARTBEAT);
    }

    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    // ── Helpers ───────────────────────────────────────────────────

    public static function eventTypes(): array
    {
        return [
            self::EVENT_REGISTER,
            self::EVENT_HEARTBEAT,
            self::EVENT_UPDATE,
            self::EVENT_ERROR,
        ];
    }

    /**
     * Record an event without having the agent instance.
     * Keeps controller code terse: AgentLog::record($agent, 'error', 'msg').
     */
    public static function record(Agent $agent, string $eventType, ?string $message = null): self
    {
        return static::create([
            'agent_id'   => $agent->id,
            'event_type' => $eventType,
            'message'    => $message,
        ]);
    }
}
