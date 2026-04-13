<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetInventory extends Model
{
    protected $fillable = [
        'ip_address', 'hostname', 'identified_scope', 'environment',
        'system_name', 'classification_level', 'critical_level',
        'os', 'open_ports', 'vuln_critical', 'vuln_high', 'vuln_medium', 'vuln_low',
        'tags', 'notes', 'status', 'last_scanned_at', 'created_by',
    ];

    protected $casts = [
        'last_scanned_at'      => 'datetime',
        'classification_level' => 'integer',
        'vuln_critical'        => 'integer',
        'vuln_high'            => 'integer',
        'vuln_medium'          => 'integer',
        'vuln_low'             => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Helpers ───────────────────────────────────────────────
    public function totalVulns(): int
    {
        return $this->vuln_critical + $this->vuln_high + $this->vuln_medium + $this->vuln_low;
    }

    public function criticalLevelColor(): string
    {
        return match ($this->critical_level) {
            'Mission-Critical'    => 'danger',
            'Business-Critical'   => 'warning',
            'Business Operational'=> 'info',
            'Administrative'      => 'secondary',
            default               => 'light',
        };
    }

    public function scopeBadgeColor(): string
    {
        return match ($this->identified_scope) {
            'PCI'        => 'danger',
            'DMZ'        => 'warning',
            'Internal'   => 'primary',
            'External'   => 'info',
            'Third-Party'=> 'secondary',
            default      => 'secondary',
        };
    }

    public function envBadgeColor(): string
    {
        return match ($this->environment) {
            'PROD'  => 'danger',
            'UAT'   => 'warning',
            'STAGE' => 'info',
            default => 'secondary',
        };
    }

    // ── Auto-classification logic ─────────────────────────────

    /**
     * Determine scope from IP, hostname, ports, and tags.
     */
    public static function classifyScope(string $ip, ?string $hostname, ?string $ports, ?string $tags): string
    {
        $combined = strtolower(($hostname ?? '') . ' ' . ($tags ?? ''));

        // PCI keywords
        if (preg_match('/\b(payment|card|pci|pos|cardholder)\b/', $combined)) {
            return 'PCI';
        }

        // Cloud/vendor
        if (preg_match('/\b(aws|azure|gcp|cloud|vendor|third.?party|saas)\b/', $combined)) {
            return 'Third-Party';
        }

        // Private IP ranges → Internal
        if (preg_match('/^(10\.|172\.(1[6-9]|2\d|3[01])\.|192\.168\.)/', $ip)) {
            // But if ports 80/443 exposed → DMZ
            $portList = array_map('trim', explode(',', $ports ?? ''));
            if (array_intersect(['80', '443', '8080', '8443'], $portList)) {
                return 'DMZ';
            }
            return 'Internal';
        }

        // Public IP with web ports → DMZ
        $portList = array_map('trim', explode(',', $ports ?? ''));
        if (array_intersect(['80', '443', '8080', '8443', '22'], $portList)) {
            return 'DMZ';
        }

        return 'External';
    }

    /**
     * Determine environment from hostname.
     */
    public static function classifyEnvironment(?string $hostname): string
    {
        $h = strtolower($hostname ?? '');

        if (preg_match('/\b(prod|production|prd)\b/', $h)) return 'PROD';
        if (preg_match('/\b(uat|user.?acceptance)\b/', $h))  return 'UAT';
        if (preg_match('/\b(stg|stage|staging|pre.?prod)\b/', $h)) return 'STAGE';

        return 'PROD'; // default
    }

    /**
     * Determine system name from hostname / tags.
     */
    public static function classifySystemName(?string $hostname, ?string $tags): string
    {
        $combined = strtolower(($hostname ?? '') . ' ' . ($tags ?? ''));

        if (preg_match('/\b(core|cbs)\b/', $combined) && preg_match('/\b(bank|db|database)\b/', $combined)) return 'Core Banking';
        if (preg_match('/\bpayment.?gateway\b/', $combined))  return 'Payment Gateway';
        if (preg_match('/\b(payment|card|pos)\b/', $combined)) return 'Payment Gateway';
        if (preg_match('/\bmobile\b/', $combined))             return 'Mobile Banking';
        if (preg_match('/\bapi.?gateway\b/', $combined))       return 'API Gateway';
        if (preg_match('/\bapi\b/', $combined))                return 'API Gateway';
        if (preg_match('/\b(db|database|mysql|mssql|oracle|postgres|mongo)\b/', $combined)) return 'Database Server';
        if (preg_match('/\b(web|http|nginx|apache)\b/', $combined)) return 'Web Server';
        if (preg_match('/\b(mail|smtp|exchange)\b/', $combined))    return 'Mail Server';
        if (preg_match('/\b(dc|ldap|ad|activedirectory|domain)\b/', $combined)) return 'Directory Server';
        if (preg_match('/\b(vpn|firewall|fw|gw|gateway)\b/', $combined)) return 'Network Gateway';

        return 'General Server';
    }

    /**
     * Determine classification level (1–5) and critical level label.
     */
    public static function classifyLevel(string $scope, string $env, string $systemName, int $criticalVulns): array
    {
        $isProd  = $env === 'PROD';
        $isDB    = str_contains($systemName, 'Database') || str_contains($systemName, 'Core Banking');
        $isPayment = str_contains($systemName, 'Payment');

        if ($scope === 'PCI' || ($isProd && ($isDB || $isPayment))) {
            return [1, 'Mission-Critical'];
        }
        if ($isProd && ($scope === 'DMZ' || $criticalVulns > 0)) {
            return [2, 'Business-Critical'];
        }
        if ($isProd) {
            return [2, 'Business-Critical'];
        }
        if ($env === 'UAT' || $env === 'STAGE') {
            return [3, 'Business Operational'];
        }
        if ($scope === 'Third-Party') {
            return [5, 'None-Bank'];
        }

        return [3, 'Business Operational'];
    }
}
