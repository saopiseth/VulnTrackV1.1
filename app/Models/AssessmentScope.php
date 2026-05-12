<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\SiteSetting;

class AssessmentScope extends Model
{
    protected $fillable = [
        'group_id',
        'ip_address', 'hostname', 'system_name',
        'system_criticality', 'system_owner',
        'identified_scope', 'environment', 'location',
        'notes', 'remediation_sla', 'created_by',
    ];

    protected $casts = [
        'system_criticality' => 'integer',
    ];

    public static function scopeOptions(): array
    {
        return ['PCI', 'DMZ', 'Internal'];
    }

    public static function environmentOptions(): array
    {
        return ['PROD', 'UAT', 'STAGE'];
    }

    public static function locationOptions(): array
    {
        return ['DC', 'DR', 'Cloud'];
    }

    public static function remediationSlaOptions(): array
    {
        return ['Priority Level 1', 'Priority Level 2', 'Priority Level 3'];
    }

    public static function defaultCriticalityLabels(): array
    {
        return [
            1 => 'Mission-Critical',
            2 => 'Business-Critical',
            3 => 'Business Operational',
            4 => 'Administrative',
            5 => 'None-Bank',
        ];
    }

    private static function colorPalette(): array
    {
        return [
            ['bg' => '#fee2e2', 'color' => '#991b1b'],
            ['bg' => '#fef3c7', 'color' => '#92400e'],
            ['bg' => '#dbeafe', 'color' => '#1e40af'],
            ['bg' => '#f3f4f6', 'color' => '#374151'],
            ['bg' => '#f0fdf4', 'color' => '#166534'],
            ['bg' => '#fdf4ff', 'color' => '#7e22ce'],
            ['bg' => '#fff7ed', 'color' => '#9a3412'],
        ];
    }

    public static function criticalityLevels(): array
    {
        $stored  = SiteSetting::get('criticality_labels');
        $palette = static::colorPalette();
        $result  = [];

        if ($stored) {
            $decoded = json_decode($stored, true) ?: [];

            // New format: [{level: int, label: string}, ...]
            if (!empty($decoded) && isset($decoded[0]) && is_array($decoded[0]) && array_key_exists('level', $decoded[0])) {
                usort($decoded, fn($a, $b) => (int)$a['level'] <=> (int)$b['level']);
                foreach ($decoded as $i => $row) {
                    $result[(int)$row['level']] = array_merge(
                        $palette[$i % count($palette)],
                        ['label' => $row['label']]
                    );
                }
                return $result;
            }

            // Old format: {"1": "label", "2": "label", ...}
            if (!empty($decoded)) {
                ksort($decoded);
                $i = 0;
                foreach ($decoded as $k => $label) {
                    $result[(int)$k] = array_merge($palette[$i % count($palette)], ['label' => $label]);
                    $i++;
                }
                return $result;
            }
        }

        // Defaults
        $defaults = static::defaultCriticalityLabels();
        $i = 0;
        foreach ($defaults as $k => $label) {
            $result[$k] = array_merge($palette[$i % count($palette)], ['label' => $label]);
            $i++;
        }
        return $result;
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(AssessmentScopeGroup::class, 'group_id');
    }

    public function vulnAssessments(): BelongsToMany
    {
        return $this->belongsToMany(
            VulnAssessment::class,
            'vuln_assessment_scope',
            'assessment_scope_id',
            'vuln_assessment_id'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
