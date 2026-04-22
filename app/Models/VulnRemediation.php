<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\UserGroup;

class VulnRemediation extends Model
{
    protected $fillable = [
        'assessment_id', 'plugin_id', 'ip_address',
        'status', 'assigned_to', 'assigned_group_id', 'due_date', 'comments', 'evidence_path', 'updated_by',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public static function statuses(): array
    {
        return ['Open', 'In Progress', 'Resolved', 'Accepted Risk'];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(VulnAssessment::class, 'assessment_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function assignedGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class, 'assigned_group_id');
    }
}
