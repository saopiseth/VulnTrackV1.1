<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class InstalledSoftware extends Model
{
    protected $table = 'installed_software';

    protected $fillable = [
        'agent_id',
        'name',
        'version',
        'installed_at',
        'collected_at',
    ];

    protected $casts = [
        'installed_at' => 'datetime',
        'collected_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    // ── Scopes ────────────────────────────────────────────────────

    /**
     * Full-text style name search.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('name', 'like', '%' . $term . '%');
    }

    /**
     * Filter by exact version.
     */
    public function scopeVersion(Builder $query, string $version): Builder
    {
        return $query->where('version', $version);
    }

    /**
     * Returns distinct (name, version) pairs across all agents.
     * The foundation for a software → CVE mapping job:
     *
     *   InstalledSoftware::globalCatalog()
     *       ->join('cve_mappings', fn($j) => $j
     *           ->on('installed_software.name', '=', 'cve_mappings.product')
     *           ->on('installed_software.version', '=', 'cve_mappings.version'))
     *       ->select('installed_software.*', 'cve_mappings.cve_id')
     *       ->get();
     */
    public function scopeGlobalCatalog(Builder $query): Builder
    {
        return $query->select('name', 'version')
                     ->distinct()
                     ->orderBy('name');
    }

    /**
     * Software entries not refreshed in the last $days days — may indicate
     * a stale agent or software that was silently removed.
     */
    public function scopeStale(Builder $query, int $days = 7): Builder
    {
        return $query->where('collected_at', '<', now()->subDays($days));
    }

    // ── Static helpers ─────────────────────────────────────────────

    /**
     * Cross-agent count: how many agents have this software+version installed.
     * Useful for impact assessment ("how many machines have Log4j 2.14?").
     */
    public static function agentCountFor(string $name, string $version): int
    {
        return static::where('name', $name)
                     ->where('version', $version)
                     ->count();
    }
}
