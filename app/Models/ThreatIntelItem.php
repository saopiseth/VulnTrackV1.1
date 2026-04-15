<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThreatIntelItem extends Model
{
    protected $table = 'threat_intel_items';

    protected $fillable = [
        'title', 'type', 'cve_id', 'cvss_score', 'severity',
        'description', 'affected_products', 'source', 'source_url',
        'published_at', 'status', 'tags', 'ioc_type', 'ioc_value', 'created_by',
    ];

    protected $casts = [
        'tags'         => 'array',
        'published_at' => 'date',
        'cvss_score'   => 'float',
    ];

    public static function types(): array
    {
        return ['CVE', 'Advisory', 'IOC', 'Exploit', 'Campaign'];
    }

    public static function severities(): array
    {
        return ['Critical', 'High', 'Medium', 'Low', 'Info'];
    }

    public static function statuses(): array
    {
        return ['Active', 'Monitoring', 'Mitigated', 'Archived'];
    }

    public static function iocTypes(): array
    {
        return ['IP', 'Domain', 'Hash', 'URL'];
    }

    public static function severityStyle(string $severity): array
    {
        return match ($severity) {
            'Critical' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'icon' => 'bi-exclamation-octagon-fill'],
            'High'     => ['bg' => '#ffedd5', 'color' => '#9a3412', 'icon' => 'bi-exclamation-triangle-fill'],
            'Medium'   => ['bg' => '#fef9c3', 'color' => '#854d0e', 'icon' => 'bi-exclamation-circle-fill'],
            'Low'      => ['bg' => '#f1f5f9', 'color' => '#475569', 'icon' => 'bi-info-circle-fill'],
            'Info'     => ['bg' => '#e0f2fe', 'color' => '#0c4a6e', 'icon' => 'bi-info-circle'],
            default    => ['bg' => '#f3f4f6', 'color' => '#374151', 'icon' => 'bi-question-circle'],
        };
    }

    public static function typeStyle(string $type): array
    {
        return match ($type) {
            'CVE'      => ['bg' => '#fee2e2', 'color' => '#991b1b', 'icon' => 'bi-shield-exclamation'],
            'Advisory' => ['bg' => '#fef3c7', 'color' => '#92400e', 'icon' => 'bi-megaphone-fill'],
            'IOC'      => ['bg' => '#ede9fe', 'color' => '#5b21b6', 'icon' => 'bi-radioactive'],
            'Exploit'  => ['bg' => '#ffedd5', 'color' => '#7c2d12', 'icon' => 'bi-lightning-fill'],
            'Campaign' => ['bg' => '#fce7f3', 'color' => '#9d174d', 'icon' => 'bi-person-fill-exclamation'],
            default    => ['bg' => '#f3f4f6', 'color' => '#374151', 'icon' => 'bi-tag'],
        };
    }

    public static function statusStyle(string $status): array
    {
        return match ($status) {
            'Active'     => ['bg' => '#fee2e2', 'color' => '#991b1b', 'icon' => 'bi-record-circle-fill'],
            'Monitoring' => ['bg' => '#fef9c3', 'color' => '#854d0e', 'icon' => 'bi-eye-fill'],
            'Mitigated'  => ['bg' => '#d1fae5', 'color' => '#065f46', 'icon' => 'bi-check-circle-fill'],
            'Archived'   => ['bg' => '#f1f5f9', 'color' => '#94a3b8', 'icon' => 'bi-archive-fill'],
            default      => ['bg' => '#f3f4f6', 'color' => '#374151', 'icon' => 'bi-circle'],
        };
    }

    public static function cvssColor(float|null $score): string
    {
        if (is_null($score)) return '#94a3b8';
        if ($score >= 9.0)   return '#991b1b';
        if ($score >= 7.0)   return '#9a3412';
        if ($score >= 4.0)   return '#854d0e';
        return '#065f46';
    }

    public static function cvssLabel(float|null $score): string
    {
        if (is_null($score)) return '—';
        if ($score >= 9.0)   return 'Critical';
        if ($score >= 7.0)   return 'High';
        if ($score >= 4.0)   return 'Medium';
        return 'Low';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
