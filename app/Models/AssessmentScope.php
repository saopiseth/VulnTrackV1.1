<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AssessmentScope extends Model
{
    protected $fillable = [
        'group_id',
        'ip_address', 'hostname', 'system_name',
        'system_criticality', 'system_owner',
        'identified_scope', 'environment', 'location',
        'notes', 'created_by',
    ];

    protected $casts = [
        'system_criticality' => 'integer',
    ];

    public static function scopeOptions(): array
    {
        return ['PCI', 'DMZ', 'Internal'];
    }

    public static function environmentOptions(): array
    {
        return ['PROD', 'UAT', 'STAGE'];
    }

    public static function locationOptions(): array
    {
        return ['DC', 'DR', 'Cloud'];
    }

    public static function criticalityLevels(): array
    {
        return [
            1 => ['label' => 'Mission-Critical',     'bg' => '#fee2e2', 'color' => '#991b1b'],
            2 => ['label' => 'Business-Critical',    'bg' => '#fef3c7', 'color' => '#92400e'],
            3 => ['label' => 'Business Operational', 'bg' => '#dbeafe', 'color' => '#1e40af'],
            4 => ['label' => 'Administrative',       'bg' => '#f3f4f6', 'color' => '#374151'],
            5 => ['label' => 'None-Bank',             'bg' => '#f0fdf4', 'color' => '#166534'],
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(AssessmentScopeGroup::class, 'group_id');
    }

    public function vulnAssessments(): BelongsToMany
    {
        return $this->belongsToMany(
            VulnAssessment::class,
            'vuln_assessment_scope',
            'assessment_scope_id',
            'vuln_assessment_id'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
