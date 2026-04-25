<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'event', 'subject_type', 'subject_id', 'context', 'ip_address',
    ];

    protected $casts = ['context' => 'array'];

    protected static function booted(): void
    {
        static::creating(fn($m) => $m->created_at ??= now());
    }

    public static function record(string $event, mixed $subject = null, array $context = []): void
    {
        try {
            static::create([
                'user_id'      => auth()->id(),
                'event'        => $event,
                'subject_type' => $subject ? class_basename($subject) : null,
                'subject_id'   => $subject?->id,
                'context'      => $context ?: null,
                'ip_address'   => request()->ip(),
            ]);
        } catch (\Throwable) {
            // Never let audit failure break a user-facing request
        }
    }
}
