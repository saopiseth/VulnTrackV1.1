<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VulnAssessment extends Model
{
    protected $fillable = [
        'name', 'description', 'scan_date', 'period_start', 'period_end',
        'environment', 'scanner_type', 'created_by',
    ];

    protected $casts = [
        'scan_date'    => 'date',
        'period_start' => 'date',
        'period_end'   => 'date',
    ];

    public static function environments(): array
    {
        return ['Production', 'UAT', 'Internal', 'Development'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scans(): HasMany
    {
        return $this->hasMany(VulnScan::class, 'assessment_id')->orderBy('created_at');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(VulnFinding::class, 'assessment_id');
    }

    public function remediations(): HasMany
    {
        return $this->hasMany(VulnRemediation::class, 'assessment_id');
    }

    public function baselineScan(): ?VulnScan
    {
        return $this->scans()->where('is_baseline', true)->reorder()->orderBy('id')->first();
    }

    public function latestScan(): ?VulnScan
    {
        // Skip empty scans (re-uploads that produced 0 findings due to deduplication).
        // reorder() clears the relationship's default ASC sort so ->orderByDesc works correctly.
        return $this->scans()->where('is_baseline', false)->where('finding_count', '>', 0)->reorder()->orderByDesc('id')->first();
    }
}
