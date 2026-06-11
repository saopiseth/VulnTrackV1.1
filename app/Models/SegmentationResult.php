<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SegmentationResult extends Model
{
    protected $fillable = [
        'segmentation_test_id', 'target_subnet', 'status', 'host_count',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(SegmentationTest::class, 'segmentation_test_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(SegmentationDetail::class);
    }
}
