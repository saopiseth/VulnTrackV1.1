<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VulnScan extends Model
{
    protected $fillable = [
        'assessment_id', 'filename', 'is_baseline', 'finding_count', 'host_count', 'notes', 'created_by',
    ];

    protected $casts = [
        'is_baseline' => 'boolean',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(VulnAssessment::class, 'assessment_id');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(VulnFinding::class, 'scan_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Unique key used for baseline vs latest comparison */
    public function fingerprintSet(): \Illuminate\Support\Collection
    {
        return $this->findings()
            ->selectRaw('plugin_id || "|" || ip_address as fp')
            ->pluck('fp');
    }

    /** Unique IP addresses seen in this scan */
    public function hostSet(): \Illuminate\Support\Collection
    {
        return $this->findings()
            ->distinct()
            ->pluck('ip_address');
    }
}
