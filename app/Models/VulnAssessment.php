<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class VulnAssessment extends Model
{
    protected $fillable = [
        'name', 'description', 'scan_date', 'period_start', 'period_end',
        'environment', 'scanner_type', 'scope_group_id', 'sla_policy_id', 'created_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected $casts = [
        'scan_date'    => 'date',
        'period_start' => 'date',
        'period_end'   => 'date',
    ];

    public static function environments(): array
    {
        return ['Production', 'UAT', 'Internal', 'Development'];
    }

    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class, 'sla_policy_id');
    }

    public function scopeGroup(): BelongsTo
    {
        return $this->belongsTo(AssessmentScopeGroup::class, 'scope_group_id');
    }

    public function scopeEntries(): BelongsToMany
    {
        return $this->belongsToMany(
            AssessmentScope::class,
            'vuln_assessment_scope',
            'vuln_assessment_id',
            'assessment_scope_id'
        );
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
