<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VulnTrackedHistory extends Model
{
    protected $table = 'vuln_tracked_history';

    public $timestamps = false;

    protected $fillable = [
        'tracked_id', 'scan_id',
        'event_type',
        'prev_status', 'new_status',
        'prev_severity', 'new_severity',
        'note', 'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function tracked(): BelongsTo
    {
        return $this->belongsTo(VulnTracked::class, 'tracked_id');
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(VulnScan::class, 'scan_id');
    }
}
