<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentScopeGroup extends Model
{
    protected $table = 'assessment_scope_groups';

    protected $fillable = ['name', 'description', 'created_by'];

    public function items(): HasMany
    {
        return $this->hasMany(AssessmentScope::class, 'group_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
