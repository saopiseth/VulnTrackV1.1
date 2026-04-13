<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VulnFinding extends Model
{
    protected $fillable = [
        'scan_id', 'assessment_id', 'ip_address', 'hostname', 'os_detected',
        'os_name', 'os_family', 'os_confidence',
        'vuln_category', 'affected_component',
        'plugin_id', 'cve', 'severity', 'vuln_name',
        'description', 'remediation_text', 'port', 'protocol',
        'plugin_output', 'scan_timestamp',
    ];

    protected $casts = [
        'scan_timestamp' => 'datetime',
    ];

    public static function severities(): array
    {
        return ['Critical', 'High', 'Medium', 'Low', 'Info'];
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(VulnScan::class, 'scan_id');
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(VulnAssessment::class, 'assessment_id');
    }

    public function remediation(): BelongsTo
    {
        return $this->belongsTo(VulnRemediation::class, 'assessment_id', 'assessment_id')
            ->where('plugin_id', $this->plugin_id)
            ->where('ip_address', $this->ip_address);
    }

    public function fingerprint(): string
    {
        return $this->plugin_id . '|' . $this->ip_address;
    }

    public static function categories(): array
    {
        return ['OS', 'Application', 'Database', 'Web Server', 'Network', 'SSL/TLS', 'Policy', 'Other'];
    }

    /** Returns [bg, color, icon] for a category badge */
    public static function categoryStyle(string $cat): array
    {
        return match ($cat) {
            'OS'          => ['#dbeafe', '#1e40af', 'bi-window-desktop'],
            'Application' => ['#fef3c7', '#92400e', 'bi-box-seam-fill'],
            'Database'    => ['#f3e8ff', '#6b21a8', 'bi-database-fill'],
            'Web Server'  => ['#d1fae5', '#065f46', 'bi-globe2'],
            'Network'     => ['#e0f2fe', '#0c4a6e', 'bi-diagram-3-fill'],
            'SSL/TLS'     => ['#ffedd5', '#9a3412', 'bi-shield-lock-fill'],
            'Policy'      => ['#f1f5f9', '#475569', 'bi-clipboard2-check-fill'],
            default       => ['#f3f4f6', '#6b7280', 'bi-question-circle-fill'],
        };
    }
}
