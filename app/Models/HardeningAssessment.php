<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class HardeningAssessment extends Model
{
    protected $fillable = [
        'uuid', 'name', 'system_name', 'hostname', 'ip_address', 'operating_system',
        'environment', 'scope_type', 'asset_owner', 'system_owner', 'criticality_level',
        'assessment_date', 'remarks', 'nessus_file_path', 'nessus_file_name',
        'nessus_file_size', 'upload_status', 'upload_error',
        'total_findings', 'compliant_count', 'non_compliant_count',
        'partially_compliant_count', 'not_applicable_count', 'created_by',
    ];

    protected $casts = [
        'assessment_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(HardeningFinding::class, 'hardening_assessment_id');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(HardeningVerification::class, 'hardening_assessment_id');
    }

    public function complianceRate(): float
    {
        if ($this->total_findings === 0) {
            return 0.0;
        }
        return round(($this->compliant_count / $this->total_findings) * 100, 1);
    }

    public function latestVerification(): ?HardeningVerification
    {
        return $this->verifications()
            ->where('upload_status', 'completed')
            ->latest()
            ->first();
    }
}
