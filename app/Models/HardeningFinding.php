<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HardeningFinding extends Model
{
    protected $fillable = [
        'hardening_assessment_id', 'plugin_id', 'plugin_name', 'plugin_family',
        'description', 'solution', 'plugin_output', 'severity', 'cvss_score',
        'cve', 'port', 'protocol', 'service', 'compliance_result',
        'compliance_status', 'finding_key',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(HardeningAssessment::class, 'hardening_assessment_id');
    }

    public function verificationResults(): HasMany
    {
        return $this->hasMany(HardeningVerificationResult::class, 'hardening_finding_id');
    }

    public function statusBadgeClass(): string
    {
        return match ($this->compliance_status) {
            'Compliant'           => 'success',
            'Non-Compliant'       => 'danger',
            'Partially Compliant' => 'warning',
            'Not Applicable'      => 'secondary',
            default               => 'secondary',
        };
    }
}
