<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProjectAssessment extends Model
{
    public static function criteriaFields(): array
    {
        return array_column(self::criteria(), 'field');
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            do {
                $slug = Str::random(12);
            } while (static::where('slug', $slug)->exists());

            $model->slug = $slug;
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $fillable = [
        'slug',
        'assessment_type',
        'project_kickoff',
        'due_date',
        'complete_date',
        'project_coordinator',
        'assessor',
        'priority',
        'bcd_id',
        // criteria booleans + evidence + status (auto-expanded below)
        'system_architecture_review', 'system_architecture_review_evidence', 'system_architecture_review_status',
        'penetration_test',           'penetration_test_evidence',           'penetration_test_status',
        'security_hardening',         'security_hardening_evidence',         'security_hardening_status',
        'vulnerability_assessment',   'vulnerability_assessment_evidence',   'vulnerability_assessment_status',
        'secure_code_review',         'secure_code_review_evidence',         'secure_code_review_status',
        'antimalware_protection',     'antimalware_protection_evidence',     'antimalware_protection_status',
        'network_security',           'network_security_evidence',           'network_security_status',
        'security_monitoring',        'security_monitoring_evidence',        'security_monitoring_status',
        'system_access_matrix',       'system_access_matrix_evidence',       'system_access_matrix_status',
        'status',
        'bcd_url',
        'comments',
        'created_by',
    ];

    protected $casts = [
        'project_kickoff'            => 'date',
        'due_date'                   => 'date',
        'complete_date'              => 'date',
        'system_architecture_review' => 'boolean',
        'penetration_test'           => 'boolean',
        'security_hardening'         => 'boolean',
        'vulnerability_assessment'   => 'boolean',
        'secure_code_review'         => 'boolean',
        'antimalware_protection'     => 'boolean',
        'network_security'           => 'boolean',
        'security_monitoring'        => 'boolean',
        'system_access_matrix'       => 'boolean',
    ];

    public static function criteria(): array
    {
        return [
            ['field' => 'system_architecture_review', 'label' => 'System Architecture Review',                  'description' => 'System Architecture shall be reviewed and acknowledged by Architecture Team.'],
            ['field' => 'penetration_test',            'label' => 'Penetration Test',                            'description' => 'A controlled security exercise that simulates real-world cyberattacks to exploit vulnerabilities in systems, applications, or networks. The goal is to validate actual security risks, measure the effectiveness of existing controls, and demonstrate potential business impact if vulnerabilities are exploited.'],
            ['field' => 'security_hardening',          'label' => 'Security Hardening',                          'description' => 'Involves strengthening systems by applying secure configuration baselines. This includes disabling unnecessary services, enforcing strong authentication, applying least privilege, securing protocols, and aligning configurations with industry standards such as CIS.'],
            ['field' => 'vulnerability_assessment',    'label' => 'Vulnerability Assessment',                    'description' => 'A systematic process of scanning and analysing systems to identify known security vulnerabilities, misconfigurations, and missing patches. Vulnerabilities are categorized by severity to help prioritize remediation and reduce overall exposure to security threats.'],
            ['field' => 'secure_code_review',          'label' => 'Secure Code Review',                          'description' => 'Examines application source code to detect security flaws such as SQL injection, cross-site scripting (XSS), insecure authentication, and improper error handling. The review ensures compliance with secure coding standards (e.g., OWASP Top 10) and reduces vulnerabilities before deployment.'],
            ['field' => 'antimalware_protection',      'label' => 'Antimalware Protection',                      'description' => 'Ensures endpoints and servers are protected against malware, ransomware, and malicious scripts through antivirus/EDR solutions. This includes real-time scanning, signature updates, behavioural detection, and centralized management to prevent and respond to malware threats.'],
            ['field' => 'network_security',            'label' => 'Network, Anti-DDoS and Application Security', 'description' => 'Implements layered security controls such as firewalls, web application firewalls (WAF), intrusion prevention systems (IPS), and anti-DDoS protections. These controls defend against network-based attacks, application-layer exploits, and denial-of-service attempts.'],
            ['field' => 'security_monitoring',         'label' => 'Security Monitoring – Log Onboarding',        'description' => 'Integrates system, application, and security logs into a centralized monitoring platform (e.g., SIEM). This enables real-time threat detection, incident investigation, compliance reporting, and alerting for suspicious or unauthorized activities.'],
            ['field' => 'system_access_matrix',        'label' => 'System Access Matrix',                        'description' => 'Defines user roles, permissions, and access levels within applications. This ensures role-based access control (RBAC), segregation of duties, and prevention of unauthorized access to sensitive application functions.'],
        ];
    }

    public static function criteriaStatuses(): array
    {
        return ['Not Started', 'In Progress', 'Completed', 'N/A'];
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'Critical' => 'danger',
            'High'     => 'warning',
            'Medium'   => 'info',
            'Low'      => 'secondary',
            default    => 'secondary',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'Open'        => 'danger',
            'In Progress' => 'warning',
            'Closed'      => 'success',
            default       => 'secondary',
        };
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
