<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentSummary extends Model
{
    protected $table      = 'assessment_summaries';
    protected $primaryKey = 'assessment_id';
    public    $timestamps = false;   // only updated_at exists; we set it manually

    protected $fillable = [
        'assessment_id',
        'active_total',
        'resolved_total',
        'critical',
        'high',
        'medium',
        'low',
        'host_count',
        'top_hosts_json',
        'updated_at',
    ];

    protected $casts = [
        'top_hosts_json' => 'array',
        'updated_at'     => 'datetime',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(VulnAssessment::class, 'assessment_id');
    }
}
