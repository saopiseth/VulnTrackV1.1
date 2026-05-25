<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class HardeningVerification extends Model
{
    protected $fillable = [
        'uuid', 'hardening_assessment_id', 'verification_date', 'verified_by',
        'remarks', 'nessus_file_path', 'nessus_file_name', 'nessus_file_size',
        'upload_status', 'upload_error',
        'resolved_count', 'still_open_count', 'new_finding_count', 'not_found_count',
        'created_by',
    ];

    protected $casts = [
        'verification_date' => 'date',
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

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(HardeningAssessment::class, 'hardening_assessment_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(HardeningVerificationResult::class, 'hardening_verification_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function resolutionRate(): float
    {
        $total = $this->resolved_count + $this->still_open_count + $this->not_found_count;
        if ($total === 0) {
            return 0.0;
        }
        return round(($this->resolved_count / $total) * 100, 1);
    }
}
