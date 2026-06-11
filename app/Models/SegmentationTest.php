<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SegmentationTest extends Model
{
    protected $fillable = [
        'uuid', 'name', 'scanner_ip', 'scanner_subnet',
        'file_path', 'file_name', 'file_size',
        'upload_status', 'upload_error', 'notes', 'created_by',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function results(): HasMany
    {
        return $this->hasMany(SegmentationResult::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(SegmentationDetail::class);
    }

    public function accessibleCount(): int
    {
        return $this->results()->where('status', 'accessible')->count();
    }

    public function notAccessibleCount(): int
    {
        return $this->results()->where('status', 'not_accessible')->count();
    }
}
