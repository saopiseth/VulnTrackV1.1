<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VulnScan extends Model
{
    protected $fillable = [
        'assessment_id', 'filename', 'scan_name', 'scan_date',
        'is_baseline', 'is_verification', 'finding_count', 'host_count',
        'notes', 'created_by', 'upload_status', 'upload_error', 'file_path',
    ];

    protected $casts = [
        'is_baseline'    => 'boolean',
        'is_verification' => 'boolean',
        'scan_date'      => 'date',
    ];

    public function isPending(): bool    { return $this->upload_status === 'pending'; }
    public function isProcessing(): bool { return $this->upload_status === 'processing'; }
    public function isCompleted(): bool  { return $this->upload_status === 'completed'; }
    public function isFailed(): bool     { return $this->upload_status === 'failed'; }

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
