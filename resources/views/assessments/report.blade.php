<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
/* ═══════════════════════════════════════════
   BASE
═══════════════════════════════════════════ */
* { box-sizing: border-box; margin: 0; padding: 0; }

p { margin-bottom: 4px; }

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 9.5pt;
    color: #1a1a1a;
    line-height: 1.6;
}

/* ═══════════════════════════════════════════
   HEADER (fixed, shown on all pages except cover)
═══════════════════════════════════════════ */
.page-header {
    position: fixed;
    top: 0; left: 0; right: 0;
    height: 36px;
    border-bottom: 2px solid #98c20a;
    background: #fff;
}
.page-header table { width: 100%; height: 36px; border-collapse: collapse; }
.page-header td    { border: none; background: transparent; vertical-align: middle; font-size: 7.5pt; }
.hdr-left  { width: 50%; padding-left: 40px; color: #5a7605; font-weight: bold; text-align: left; }
.hdr-right { width: 50%; padding-right: 40px; color: #94a3b8; text-align: right; }

/* ═══════════════════════════════════════════
   FOOTER (fixed)
═══════════════════════════════════════════ */
.page-footer {
    position: fixed;
    bottom: 0; left: 0; right: 0;
    height: 28px;
    background: #fff;
    border-top: 1px solid #e2e8f0;
}
.page-footer table { width: 100%; height: 28px; border-collapse: collapse; }
.page-footer td    { border: none; background: transparent; vertical-align: middle; font-size: 7.5pt; color: #94a3b8; }
.ftr-left  { width: 50%; padding-left: 40px; text-align: left; }
.ftr-right { width: 50%; padding-right: 40px; text-align: right; }

/* ═══════════════════════════════════════════
   PAGE BREAKS
═══════════════════════════════════════════ */
.page-break { page-break-after: always; }

/* dompdf page number */
.pagenum:before { content: counter(page); }

/* ═══════════════════════════════════════════
   COVER PAGE
═══════════════════════════════════════════ */
.cover-page {
    width: 100%;
    height: 100vh;
    background: #fff;
    page-break-after: always;
}
.cover-green-bar {
    background: linear-gradient(135deg, #2d3f01 0%, #4a6205 50%, #6b8e07 100%);
    height: 14px;
}
.cover-center-table {
    width: 100%;
    height: calc(100vh - 14px);
    border-collapse: collapse;
}
.cover-center-cell {
    text-align: center;
    vertical-align: middle;
    padding: 40px 55px;
    border: none;
}
.cover-logo-wrap {
    margin: 0 auto 50px;
}
.cover-logo-wrap img {
    height: 70px;
    width: auto;
    display: block;
    margin: 0 auto;
}
.cover-divider {
    width: 80px;
    height: 4px;
    background: linear-gradient(90deg, #98c20a, #5a7605);
    border-radius: 2px;
    margin: 0 auto 40px;
}
.cover-tag {
    display: inline-block;
    background: #eef8d0;
    border: 1px solid #c8e87a;
    border-radius: 20px;
    padding: 5px 20px;
    font-size: 8pt;
    font-weight: bold;
    color: #4a6205;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 22px;
}
.cover-title {
    font-size: 22pt;
    font-weight: bold;
    color: #1a2e05;
    line-height: 1.25;
    letter-spacing: -0.3px;
    margin-bottom: 10px;
}
.cover-project {
    font-size: 13pt;
    color: #5a7605;
    font-weight: bold;
    margin-bottom: 40px;
    line-height: 1.4;
}
.cover-meta-box {
    display: inline-block;
    background: #f8fbf0;
    border: 1px solid #d4eaa0;
    border-radius: 10px;
    padding: 18px 40px;
    min-width: 320px;
    margin-bottom: 50px;
}
.cover-meta-table { width: 100%; border-collapse: collapse; }
.cover-meta-table td { border: none; background: transparent; padding: 5px 0; border-bottom: 1px solid #e8f2c8; font-size: 9pt; }
.cover-meta-table tr:last-child td { border-bottom: none; }
.cover-meta-label { color: #94a3b8; font-weight: bold; font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; width: 50%; }
.cover-meta-value { color: #1a2e05; font-weight: bold; text-align: right; }
.cover-confidential {
    display: inline-block;
    background: #fee2e2;
    border: 1px solid #fca5a5;
    border-radius: 6px;
    padding: 5px 18px;
    font-size: 8pt;
    font-weight: bold;
    color: #991b1b;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    margin-bottom: 16px;
}
.cover-footer-text {
    font-size: 7.5pt;
    color: #94a3b8;
    margin-top: 8px;
}

/* ═══════════════════════════════════════════
   CONTENT PAGES
═══════════════════════════════════════════ */
.content-page {
    padding: 10px 40px 16px;
}

/* ── Section titles ── */
.section-title {
    font-size: 11pt;
    font-weight: bold;
    background: linear-gradient(135deg, #374802 0%, #5a7605 100%);
    color: #fff;
    padding: 0px 0px;
    border-radius: 5px;
    margin: 0px 0 0px;
    letter-spacing: 0.2px;
}
.section-title.first { margin-top: 0; }
.subsection-title {
    font-size: 9.5pt;
    font-weight: bold;
    color: #374802;
    padding: 2px 0 2px 8px;
    margin: 4px 0 4px;
}

/* ── TOC ── */
.toc-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
.toc-table td { border: none; background: transparent; padding: 4px 0; vertical-align: bottom; font-size: 10pt; color: #1a1a1a; }
.toc-label { width: 85%; }
.toc-dots  { width: 100%; border-bottom: 1px dotted #999; display: block; margin-bottom: 2px; }
.toc-pg    { width: 15%; text-align: right; font-size: 10pt; color: #1a1a1a; white-space: nowrap; padding-left: 4px; }
.toc-h1    { font-weight: bold; }
.toc-h2    { padding-left: 24px !important; font-weight: normal; font-size: 9.5pt; }
.toc-h3    { padding-left: 48px !important; font-weight: normal; font-size: 9.5pt; }

/* ── Tables ── */
table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
th, td { padding: 5px 9px; border: 1px solid #c8d9a0; font-size: 9pt; vertical-align: top; }
th { background: #deeea8; color: #2d4a00; font-weight: bold; text-align: left; }
tr:nth-child(even) td { background: #f7faee; }

/* info table label cells */
.lbl {
    background: #edf6cc;
    color: #3d5400;
    font-weight: bold;
    width: 28%;
    white-space: nowrap;
    vertical-align: middle;
}
.lbl-wide { width: 36%; }

/* ── Disclaimer ── */
.disclaimer {
    background: #fffbeb;
    padding: 8px 12px;
    font-size: 8.5pt;
    color: #78350f;
    margin: 6px 0;
    border-radius: 0 6px 6px 0;
}

/* ── Signature line ── */
.sig-line {
    border-bottom: 1px solid #ccc;
    min-height: 28px;
    margin-top: 4px;
}

/* ── Status checkboxes ── */
.status-table td { vertical-align: middle; }
.cb-symbol { font-size: 14pt; vertical-align: middle; margin-right: 6px; }
.row-ready   td { background: #eef8d0 !important; }
.row-notready td { background: #fff5f5 !important; }

/* ── Chips ── */
.chip { padding: 2px 10px; border-radius: 12px; font-size: 8pt; font-weight: bold; display: inline-block; }
.chip-completed    { background: #d1fae5; color: #065f46; }
.chip-inprogress   { background: #fef9c3; color: #854d0e; }
.chip-notstarted   { background: #fee2e2; color: #991b1b; }
.chip-na           { background: #f1f5f9; color: #64748b; }
.chip-compiled     { background: #d1fae5; color: #065f46; }
.chip-acceptable   { background: #e8f5c2; color: #374802; }
.chip-notcomplied  { background: #fee2e2; color: #991b1b; }

/* ── Priority / status badges ── */
.badge { padding: 3px 10px; border-radius: 12px; font-size: 8pt; font-weight: bold; }
.badge-critical { background: #fee2e2; color: #991b1b; }
.badge-high     { background: #ffedd5; color: #9a3412; }
.badge-medium   { background: #e0f2fe; color: #0c4a6e; }
.badge-low      { background: #f1f5f9; color: #475569; }
.badge-open     { background: #fee2e2; color: #991b1b; }
.badge-progress { background: #fef9c3; color: #854d0e; }
.badge-closed   { background: #d1fae5; color: #065f46; }
</style>
</head>
<body>

@php
    use Carbon\Carbon;



    // ── Date helpers ──
    $fmt       = fn($d) => $d ? $d->format('d-M-Y') : '—';
    $reportDate   = now()->format('d M Y');
    $startDate    = $fmt($assessment->project_kickoff);
    $completeDate = $fmt($assessment->complete_date);
    $dueDate      = $fmt($assessment->due_date);
    $version      = '1.0';

    // ── Go-live status ──
    $isReady = $assessment->status === 'Closed';

    // ── Criteria helpers ──
    $criteriaList = \App\Models\ProjectAssessment::criteria();

    $templateLabels = [
        'system_architecture_review' => 'Architecture Review',
        'penetration_test'           => 'Penetration Test',
        'security_hardening'         => 'Security Hardening',
        'vulnerability_assessment'   => 'Vulnerability Assessment',
        'secure_code_review'         => 'Secure Code Review',
        'antimalware_protection'     => 'Security Controls (EDR/XDR)',
        'network_security'           => 'Secure Network, Anti-DDoS and Application Security',
        'security_monitoring'        => 'Security Monitoring – Log Onboarding',
        'system_access_matrix'       => 'User Access Control Matrix',
    ];

    $getStatus = function(string $field) use ($assessment): string {
        if (!$assessment->$field) return 'Not Applicable';
        return match($assessment->{$field.'_status'}) {
            'Completed'   => 'Completed',
            'In Progress' => 'In Progress',
            'N/A'         => 'Not Applicable',
            default       => 'Not Started',
        };
    };

    $getChip = fn(string $s): string => match($s) {
        'Completed'      => 'chip-completed',
        'In Progress'    => 'chip-inprogress',
        'Not Applicable' => 'chip-na',
        default          => 'chip-notstarted',
    };

    $priorityBadge = match($assessment->priority) {
        'Critical' => 'badge-critical',
        'High'     => 'badge-high',
        'Medium'   => 'badge-medium',
        default    => 'badge-low',
    };
    $statusBadge = match($assessment->status) {
        'Open'        => 'badge-open',
        'In Progress' => 'badge-progress',
        'Closed'      => 'badge-closed',
        default       => 'badge-low',
    };
@endphp

{{-- ╔═══════════════════════════════════════╗
     ║          PAGE 1 — COVER               ║
     ╚═══════════════════════════════════════╝ --}}
<div class="cover-page">

    <div class="cover-green-bar"></div>

    @php
        $logoPath = public_path('wb-logo-color.png');
        $logoB64  = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;
    @endphp

    {{-- 3-zone cover layout --}}
    <table style="width:100%;height:calc(100vh - 14px);border-collapse:collapse">

        {{-- TOP: Logo --}}
        <tr style="height:20%">
            <td style="text-align:center;vertical-align:middle;padding-top:40px;padding-bottom:10px;border:none;background:transparent">
                @if($logoB64)
                    <img src="{{ $logoB64 }}" style="height:90px;width:auto;display:inline-block">
                @endif
            </td>
        </tr>

        {{-- MIDDLE: Title & meta --}}
        <tr style="height:60%">
            <td style="text-align:center;vertical-align:middle;padding:0 55px;border:none;background:transparent">

                <div class="cover-divider"></div>
                <div class="cover-tag">Security Assessment</div>
                <div class="cover-title">Security Assessment Report</div>
                <div class="cover-project">{{ $assessment->assessment_type }}</div>

                <div class="cover-meta-box" style="margin-bottom:0">
                    <table class="cover-meta-table">
                        <tr>
                            <td class="cover-meta-label">Report Date</td>
                            <td class="cover-meta-value">{{ $reportDate }}</td>
                        </tr>
                        <tr>
                            <td class="cover-meta-label">Version</td>
                            <td class="cover-meta-value">V{{ $version }}</td>
                        </tr>
                        <tr>
                            <td class="cover-meta-label">Prepared By</td>
                            <td class="cover-meta-value">{{ $assessment->assessor ?? $assessment->creator?->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="cover-meta-label">Status</td>
                            <td class="cover-meta-value"><span class="badge {{ $statusBadge }}">{{ $assessment->status }}</span></td>
                        </tr>
                        <tr>
                            <td class="cover-meta-label">Priority</td>
                            <td class="cover-meta-value"><span class="badge {{ $priorityBadge }}">{{ $assessment->priority }}</span></td>
                        </tr>
                    </table>
                </div>

            </td>
        </tr>


    </table>

</div>

{{-- ─── Shared header & footer (appear on pages 2+) ─── --}}
<div class="page-header">
    <table><tr>
        <td class="hdr-left">Information Security &nbsp;|&nbsp; Technology Department</td>
        <td class="hdr-right">Security Assessment Report &nbsp;|&nbsp; V{{ $version }}</td>
    </tr></table>
</div>

<div class="page-footer">
    <table><tr>
        <td class="ftr-left">Wing Bank &mdash; Confidential &amp; Proprietary</td>
        <td class="ftr-right">Page <span class="pagenum"></span></td>
    </tr></table>
</div>

{{-- ╔═══════════════════════════════════════╗
     ║       PAGE 2 — TABLE OF CONTENTS      ║
     ╚═══════════════════════════════════════╝ --}}
<div class="content-page page-break">

    <div style="font-size:13pt;font-weight:bold;color:#1a1a1a;margin-bottom:14px">Table of Contents</div>

    <table class="toc-table">
        @php
        $toc = [
            ['level'=>1, 'num'=>'',   'title'=>'Table of Contents',             'page'=>2],
            ['level'=>1, 'num'=>'1.', 'title'=>'Assessment Summary',            'page'=>4],
            ['level'=>1, 'num'=>'2.', 'title'=>'Assessment Status',             'page'=>4],
            ['level'=>1, 'num'=>'3.', 'title'=>'Criteria and Status',           'page'=>4],
            ['level'=>1, 'num'=>'',   'title'=>'Appendix: Observation Status',  'page'=>5],
        ];
        @endphp
        @foreach($toc as $row)
        <tr>
            <td class="toc-label {{ $row['level']===1 ? 'toc-h1' : 'toc-h2' }}">
                @if($row['num'])<span style="margin-right:6px">{{ $row['num'] }}</span>@endif{{ $row['title'] }}
                <span class="toc-dots"></span>
            </td>
            <td class="toc-pg">{{ $row['page'] }}</td>
        </tr>
        @endforeach
    </table>

</div>

{{-- ╔═══════════════════════════════════════╗
     ║   PAGE 3 — DOCUMENT INFORMATION &     ║
     ║            APPROVAL                   ║
     ╚═══════════════════════════════════════╝ --}}
<div class="content-page page-break">
    <div class="section-title first">Document and Control Information</div>
    <div class="subsection-title">1. Document Information</div>
    <table>
        <tr>
            <td class="lbl">Document Name</td>
            <td colspan="3">Security Assessment Report — {{ $assessment->assessment_type }}</td>
        </tr>
        <tr>
            <td class="lbl">Version</td>
            <td style="width:22%">{{ $version }}</td>
            <td class="lbl" style="width:28%">Date</td>
            <td>{{ $reportDate }}</td>
        </tr>
        <tr>
            <td class="lbl">Author</td>
            <td>IT Security</td>
            <td class="lbl">Classification</td>
            <td>
                <span style="background:#fee2e2;color:#991b1b;border-radius:12px;padding:2px 10px;font-size:8pt;font-weight:bold">
                    &#128274; Confidential / Internal
                </span>
            </td>
        </tr>
        <tr>
            <td class="lbl">Owner</td>
            <td>Wing (Cambodia) Bank Plc.</td>
            <td class="lbl">Contact</td>
            <td>It.security@wingbank.com.kh</td>
        </tr>
        <tr>
            <td class="lbl">Start Date</td>
            <td>{{ $startDate }}</td>
            <td class="lbl">Complete Date</td>
            <td>{{ $completeDate }}</td>
        </tr>
        <tr>
            <td class="lbl">Due Date</td>
            <td>{{ $dueDate }}</td>
            <td class="lbl">BCD Reference</td>
            <td>{{ $assessment->bcd_id ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Project Coordinator</td>
            <td>{{ $assessment->project_coordinator ?? '—' }}</td>
            <td class="lbl">Priority</td>
            <td><span class="badge {{ $priorityBadge }}">{{ $assessment->priority }}</span></td>
        </tr>
    </table>
    <div class="disclaimer">
        <strong>Disclaimer:</strong> This document contains information that is strictly confidential
        and intended solely for authorized personnel of Wing Bank. If you have received this document
        in error, please notify the document owner immediately. Do not share, distribute, or disclose
        any part of this information to any third party without explicit consent. Please delete this
        document from your records if you are not the intended recipient.
    </div>
    <div class="subsection-title">2. Approval Information</div>
    <table>
        <thead>
            <tr>
                <th style="width:22%">Functional Role</th>
                <th style="width:30%">Name / Position</th>
                <th style="width:30%">Signature</th>
                <th style="width:18%">Date</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div style="font-size:7.5pt;color:#94a3b8;margin-bottom:2px">Information Security</div>
                    <strong>Prepared by</strong>
                </td>
                <td>{{ $assessment->assessor ?? $assessment->creator?->name ?? '—' }}</td>
                <td><div class="sig-line"></div></td>
                <td>{{ $reportDate }}</td>
            </tr>
            <tr>
                <td><strong>Reviewed by</strong></td>
                <td>{{ $assessment->project_coordinator ?? '—' }}</td>
                <td><div class="sig-line"></div></td>
                <td></td>
            </tr>
            <tr>
                <td><strong>Approved by</strong></td>
                <td></td>
                <td><div class="sig-line"></div></td>
                <td></td>
            </tr>
        </tbody>
    </table>

</div>

{{-- ╔════════════════════════════════════════════╗
     ║  PAGE 4 — SUMMARY, STATUS & CRITERIA       ║
     ╚════════════════════════════════════════════╝ --}}
<div class="content-page page-break">
    <div class="section-title first">1. Assessment Summary</div>
    <div class="subsection-title">1. Assessment Summary</div>
    <p style="font-size:9pt;color:#374151;line-height:1.6;margin:0">
        @if($assessment->comments && trim($assessment->comments))
            {{ $assessment->comments }}
        @else
            This assessment is conducted to evaluate and strengthen the Wing Bank —
            <strong>{{ $assessment->assessment_type }}</strong> project.
            Based on our review, the standard security assessment criteria are not fully
            applicable to this project, as the change involves parameter configuration only
            and does not introduce new functionalities, system integrations, or modifications.
            Therefore, the security risk is considered minimal.
        @endif
    </p>
    <div class="section-title">2. Assessment Status</div>
    <div class="subsection-title">2. Assessment Status</div>
    <p style="font-size:9pt;color:#374151;line-height:1.6;margin:0 0 4px">
        The assessment status indicates whether the system or application is adequately protected
        and approved for use based on the evaluation results.
    </p>
    <table class="status-table">
        <thead>
            <tr>
                <th style="width:30%">Status</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr class="{{ $isReady ? 'row-ready' : '' }}">
                <td>
                    <span class="cb-symbol" style="color:{{ $isReady ? '#374802' : '#ccc' }}">{{ $isReady ? '☑' : '☐' }}</span>
                    <strong style="color:#065f46">Ready to go-live</strong>
                </td>
                <td style="font-size:8.5pt">
                    Security controls have been adequately implemented, identified risks are within
                    acceptable levels, and the system/application is approved for production use.
                </td>
            </tr>
            <tr class="{{ !$isReady ? 'row-notready' : '' }}">
                <td>
                    <span class="cb-symbol" style="color:{{ !$isReady ? '#dc2626' : '#ccc' }}">{{ !$isReady ? '☑' : '☐' }}</span>
                    <strong style="color:#991b1b">Not ready to go-live</strong>
                </td>
                <td style="font-size:8.5pt">
                    Critical security gaps or unresolved risks remain, and the system/application
                    is not approved for production use until remediation is completed.
                </td>
            </tr>
        </tbody>
    </table>
    <div class="section-title">3. Criteria and Status</div>
    <div class="subsection-title">3. Criteria and Status</div>
    <p style="font-size:9pt;color:#374151;line-height:1.6;margin:0 0 4px">
        The following table defines the key security criteria used to assess the system's overall
        security posture. Each criterion evaluates the effectiveness of technical and operational
        security controls in reducing risk from cyber-attacks.
    </p>
    <table>
        <thead>
            <tr>
                <th style="width:5%;text-align:center">No</th>
                <th style="width:23%">Criteria</th>
                <th style="width:56%">Observation and Recommendation</th>
                <th style="width:16%;text-align:center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($criteriaList as $i => $c)
            @php
                $field  = $c['field'];
                $status = $getStatus($field);
                $chip   = $getChip($status);
                $label  = $templateLabels[$field] ?? $c['label'];
            @endphp
            <tr>
                <td style="text-align:center">{{ $i + 1 }}</td>
                <td><strong>{{ $label }}</strong></td>
                <td style="font-size:8.5pt;color:#374151">{{ $c['description'] }}</td>
                <td style="text-align:center"><span class="chip {{ $chip }}">{{ $status }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>

</div>

{{-- ╔═══════════════════════════════════════════╗
     ║  PAGE 6 — APPENDIX: OBSERVATION STATUS    ║
     ╚═══════════════════════════════════════════╝ --}}
<div class="content-page">
    <div class="section-title first">Appendix: Observation Status</div>
    <div class="subsection-title">4. Observation Status</div>
    <p style="font-size:9pt;color:#374151;line-height:1.6;margin:0 0 4px">
        The observation status classifications used in this report are defined as follows:
    </p>
    <table>
        <thead>
            <tr>
                <th style="width:22%">Status</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><span class="chip chip-compiled">Compiled</span></td>
                <td>The control is fully implemented and meets defined requirements.</td>
            </tr>
            <tr>
                <td><span class="chip chip-acceptable">Acceptable</span></td>
                <td>Minor gaps identified; overall risk is low and within acceptable tolerance.</td>
            </tr>
            <tr>
                <td><span class="chip chip-notcomplied">Not Complied</span></td>
                <td>The control is not implemented or does not meet required standards.</td>
            </tr>
            <tr>
                <td><span class="chip chip-na">Not Applicable</span></td>
                <td>The control does not apply to the assessed system, scope, or environment.</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top:30px;border-top:1px solid #e2e8f0;padding-top:14px;text-align:center">
        <div style="font-size:8pt;color:#94a3b8">— End of Report —</div>
    </div>

</div>

</body>
</html>
