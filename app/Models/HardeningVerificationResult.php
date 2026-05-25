<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HardeningVerificationResult extends Model
{
    protected $fillable = [
        'hardening_verification_id', 'hardening_finding_id', 'plugin_id', 'plugin_name',
        'plugin_family', 'description', 'solution', 'plugin_output', 'severity',
        'cvss_score', 'compliance_result', 'compliance_status', 'verification_status',
        'finding_key',
    ];

    public function verification(): BelongsTo
    {
        return $this->belongsTo(HardeningVerification::class, 'hardening_verification_id');
    }

    public function originalFinding(): BelongsTo
    {
        return $this->belongsTo(HardeningFinding::class, 'hardening_finding_id');
    }

    public function statusBadgeClass(): string
    {
        return match ($this->verification_status) {
            'Resolved'                  => 'success',
            'Still Open'                => 'danger',
            'New Finding'               => 'warning',
            'Accepted Risk'             => 'info',
            'Not Found in Verification' => 'secondary',
            default                     => 'secondary',
        };
    }
}
