<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SegmentationDetail extends Model
{
    protected $fillable = [
        'segmentation_test_id', 'segmentation_result_id',
        'host_ip', 'target_subnet', 'port', 'protocol', 'service',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(SegmentationTest::class, 'segmentation_test_id');
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(SegmentationResult::class, 'segmentation_result_id');
    }
}
