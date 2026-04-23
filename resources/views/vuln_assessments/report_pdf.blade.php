<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #1e293b; background: #fff; }

/* ── Fixed header on every page ── */
.header {
    position: fixed; top: 0; left: 0; right: 0;
    font-size: 7pt; color: #64748b;
    border-bottom: 1px solid #e2e8f0; background: #fff;
}
.header table { width: 100%; border: none; margin: 0; font-size: 7pt; }
.header td    { border: none; padding: 4px 40px; vertical-align: middle; }
.header .h-company { font-weight: bold; color: {{ $rpt_accent }}; text-align: left; }
.header .h-conf    { text-transform: uppercase; letter-spacing: .5px; text-align: right; color: #94a3b8; }

/* ── Fixed footer on every page ── */
.footer {
    position: fixed; bottom: 0; left: 0; right: 0;
    font-size: 7pt; color: #94a3b8; text-align: center;
    padding: 5px 40px; border-top: 1px solid #e2e8f0; background: #fff;
}

/* ── Page sections ── */
.page { padding: 48px 40px 48px; }
.page-break { page-break-after: always; }

/* ── Cover ── */
.cover {
    text-align: center;
    padding: 70px 40px 40px;
}
.cover-label {
    font-size: 7.5pt; font-weight: bold; color: {{ $rpt_accent }};
    text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 10px;
}
.cover-title { font-size: 22pt; font-weight: bold; color: #0f172a; margin-bottom: 6px; line-height: 1.15; }
.cover-sub   { font-size: 10pt; color: #64748b; margin-bottom: 28px; }
.cover-meta  { width: 55%; margin: 0 auto 32px; text-align: left; }
.cover-meta table { border: none; }
.cover-meta td { border: none; padding: 3px 8px; font-size: 9pt; }
.cover-meta .ml { color: #94a3b8; font-size: 7.5pt; text-transform: uppercase; letter-spacing: .5px; width: 38%; }
.cover-meta .mv { font-weight: bold; color: #1e293b; }
.cover-conf { font-size: 7.5pt; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 10px; margin-top: 32px; }

/* ── Section heading ── */
.sec-title {
    font-size: 10pt; font-weight: bold; color: {{ $rpt_accent }};
    text-transform: uppercase; letter-spacing: .8px;
    border-bottom: 2px solid {{ $rpt_accent }}; padding-bottom: 4px; margin-bottom: 12px;
}
.subsec-title {
    font-size: 9pt; font-weight: bold; color: #1e293b;
    margin: 14px 0 6px;
}

/* ── Tables ── */
table { width: 100%; border-collapse: collapse; font-size: 8pt; margin-bottom: 10px; }
th {
    background: #1e293b; color: #fff; font-size: 7.5pt;
    text-transform: uppercase; letter-spacing: .4px;
    padding: 5px 7px; text-align: left;
}
td { padding: 5px 7px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
tr:last-child td { border-bottom: none; }
.lbl { background: #f8fafc; color: #64748b; font-weight: bold; width: 20%; font-size: 7.5pt; }

/* ── Document info / approval table ── */
.doc-table td { border: 1px solid #e2e8f0; }
.doc-table tr:nth-child(even) td { background: #f8fafc; }

/* ── TOC ── */
.toc-1 { font-size: 9pt; font-weight: bold; padding: 4px 0; border-bottom: 1px dotted #e2e8f0; }
.toc-2 { font-size: 8.5pt; color: #475569; padding: 3px 0 3px 16px; border-bottom: 1px dotted #f1f5f9; }

/* ── Severity colours ── */
.sev-c { color: #991b1b; font-weight: bold; }
.sev-h { color: #c2410c; font-weight: bold; }
.sev-m { color: #854d0e; font-weight: bold; }
.sev-l { color: #475569; }
.open   { color: #dc2626; font-weight: bold; }
.closed { color: #16a34a; font-weight: bold; }

/* ── Pills ── */
.pill { display: inline-block; padding: 1px 6px; border-radius: 20px; font-size: 7pt; font-weight: bold; }
.pill-c { background: #fee2e2; color: #991b1b; }
.pill-h { background: #ffedd5; color: #c2410c; }
.pill-m { background: #fef9c3; color: #854d0e; }
.pill-l { background: #f1f5f9; color: #475569; }

/* ── Finding heading ── */
.finding-title {
    font-size: 9pt; font-weight: bold; color: #0f172a;
    background: #f8fafc; padding: 5px 8px;
    border-left: 3px solid {{ $rpt_accent }}; margin: 10px 0 4px;
}

/* ── Narrative text ── */
p { font-size: 8.5pt; line-height: 1.5; margin-bottom: 8px; }
</style>
</head>
<body>

<div class="header">
    <table><tr>
        <td class="h-company">{{ $rpt_company }}</td>
        <td class="h-conf">{{ $rpt_confidentiality }}</td>
    </tr></table>
</div>

<div class="footer">
    @if($rpt_footer)
        {{ $rpt_footer }}
    @else
        {{ $a->name }} &mdash; Vulnerability Assessment Report &mdash; {{ now()->format('d M Y') }} &mdash; {{ $rpt_confidentiality }}
    @endif
</div>

@php
    $sevOrder      = ['Critical', 'High', 'Medium', 'Low'];
    $totalActive   = collect($sevOrder)->sum(fn($s) => $active[$s]   ?? 0);
    $totalResolved = collect($sevOrder)->sum(fn($s) => $resolved[$s] ?? 0);
    $pillClass     = fn($s) => match($s) { 'Critical'=>'pill-c','High'=>'pill-h','Medium'=>'pill-m',default=>'pill-l' };
    $sevClass      = fn($s) => match($s) { 'Critical'=>'sev-c','High'=>'sev-h','Medium'=>'sev-m',default=>'sev-l' };
@endphp

{{-- ═══════════════════════  PAGE 1 · COVER  ═══════════════════════ --}}
<div class="cover">
    <div class="cover-label">{{ $rpt_confidentiality }}</div>
    <div class="cover-title">Vulnerability Assessment Report</div>
    <div class="cover-sub">{{ $a->name }}</div>

    <div class="cover-meta">
        <table>
            <tr><td class="ml">Organization</td><td class="mv">{{ $rpt_company }}</td></tr>
            <tr><td class="ml">Assessment Period</td>
                <td class="mv">{{ $a->period_start?->format('d M Y') ?? '—' }} – {{ $a->period_end?->format('d M Y') ?? '—' }}</td></tr>
            <tr><td class="ml">Environment</td><td class="mv">{{ $a->environment ?? '—' }}</td></tr>
            <tr><td class="ml">Prepared By</td><td class="mv">{{ $a->creator?->name ?? $rpt_prepared_by }}</td></tr>
            <tr><td class="ml">Tool Used</td><td class="mv">{{ $rpt_tool }}</td></tr>
            <tr><td class="ml">Report Date</td><td class="mv">{{ now()->format('d F Y') }}</td></tr>
        </table>
    </div>

    <div class="cover-conf">
        {{ $rpt_disclaimer }}
    </div>
</div>

{{-- ═══════════════════  PAGE 2 · DOCUMENT INFO & APPROVAL  ═══════════════════ --}}
<div class="page-break"></div>
<div class="page">
    <div class="sec-title">Document Information</div>
    <table class="doc-table">
        <tbody>
            <tr><td class="lbl">Document Title</td><td>Vulnerability Assessment Report — {{ $a->name }}</td></tr>
            <tr><td class="lbl">Version</td><td>1.0</td></tr>
            <tr><td class="lbl">Date</td><td>{{ now()->format('d F Y') }}</td></tr>
            <tr><td class="lbl">Prepared By</td><td>{{ $a->creator?->name ?? $rpt_prepared_by }}</td></tr>
            <tr><td class="lbl">Reviewed By</td><td>&nbsp;</td></tr>
            <tr><td class="lbl">Classification</td><td>{{ $rpt_confidentiality }}</td></tr>
        </tbody>
    </table>

    <div class="sec-title" style="margin-top:20px;">Approval Information</div>
    <table class="doc-table">
        <thead>
            <tr>
                <th style="width:28%">Name</th>
                <th style="width:30%">Title</th>
                <th style="width:25%">Signature</th>
                <th style="width:17%">Date</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $a->creator?->name ?? '—' }}</td>
                <td>Vulnerability Management Team</td>
                <td>&nbsp;</td>
                <td>{{ now()->format('d F Y') }}</td>
            </tr>
            <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        </tbody>
    </table>
</div>

{{-- ═══════════════════  PAGE 3 · TABLE OF CONTENTS  ═══════════════════ --}}
<div class="page-break"></div>
<div class="page">
    <div class="sec-title">Table of Contents</div>
    <br>
    <div class="toc-1">1.&nbsp;&nbsp; Executive Summary</div>
    <div class="toc-2">1.1&nbsp;&nbsp; Vulnerability Summary</div>
    <div class="toc-2">1.2&nbsp;&nbsp; Vulnerability by Host</div>
    <div class="toc-1">2.&nbsp;&nbsp; Scope of Assessment</div>
    <div class="toc-1">3.&nbsp;&nbsp; Vulnerability Assessor Information</div>
    <div class="toc-2">3.1&nbsp;&nbsp; Vulnerability Severity Ratings</div>
    <div class="toc-1">4.&nbsp;&nbsp; Technical Findings</div>
    @php $si = 1; @endphp
    @foreach($sevOrder as $sev)
    @if(!empty($findingsBySeverity[$sev]))
    <div class="toc-2">4.{{ $si++ }}&nbsp;&nbsp; {{ $sev }}-Severity Findings ({{ count($findingsBySeverity[$sev]) }} unique)</div>
    @endif
    @endforeach
</div>

{{-- ═══════════════════  PAGE 4 · EXECUTIVE SUMMARY  ═══════════════════ --}}
<div class="page-break"></div>
<div class="page">
    <div class="sec-title">1. Executive Summary</div>

    <p>This {{ $a->environment ?? '' }} Vulnerability Assessment (VA) was conducted to evaluate the current security posture
    by identifying, analysing, and prioritising vulnerabilities across critical assets including servers,
    applications, and network devices within the defined scope.</p>

    <p>The assessment was performed during the period
    <strong>{{ $a->period_start?->format('d M Y') ?? '—' }}</strong> to
    <strong>{{ $a->period_end?->format('d M Y') ?? '—' }}</strong>
    using Tenable Nessus across <strong>{{ $hostsSummary->count() }}</strong> host(s).
    A total of <strong>{{ $totalActive }}</strong> active finding(s) and
    <strong>{{ $totalResolved }}</strong> resolved finding(s) were identified.</p>

    <div class="subsec-title">1.1&nbsp; Vulnerability Summary</div>
    <table>
        <thead>
            <tr>
                <th style="width:28%">Severity</th>
                <th style="width:16%;text-align:center">Total</th>
                <th style="width:16%;text-align:center">Open</th>
                <th style="width:16%;text-align:center">Closed</th>
                <th style="width:24%;text-align:center">CVSS Range</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sevOrder as $sev)
            @php
                $openCnt  = $active[$sev]   ?? 0;
                $closeCnt = $resolved[$sev] ?? 0;
                $total    = $openCnt + $closeCnt;
                $range    = match($sev){ 'Critical'=>'9.0 – 10.0','High'=>'7.0 – 8.9','Medium'=>'4.0 – 6.9','Low'=>'0.1 – 3.9' };
            @endphp
            <tr>
                <td><span class="{{ $sevClass($sev) }}">{{ $sev }}</span></td>
                <td style="text-align:center;font-weight:bold;">{{ $total }}</td>
                <td style="text-align:center;" class="{{ $openCnt ? 'open' : '' }}">{{ $openCnt }}</td>
                <td style="text-align:center;" class="{{ $closeCnt ? 'closed' : '' }}">{{ $closeCnt }}</td>
                <td style="text-align:center;color:#64748b;font-size:7.5pt;">{{ $range }}</td>
            </tr>
            @endforeach
            <tr style="font-weight:bold;background:#f1f5f9;">
                <td>Total</td>
                <td style="text-align:center;">{{ $totalActive + $totalResolved }}</td>
                <td style="text-align:center;" class="{{ $totalActive ? 'open' : '' }}">{{ $totalActive }}</td>
                <td style="text-align:center;" class="{{ $totalResolved ? 'closed' : '' }}">{{ $totalResolved }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="subsec-title">1.2&nbsp; Vulnerability by Host</div>
    @if($hostsSummary->count())
    <table>
        <thead>
            <tr>
                <th style="width:5%;text-align:center">No</th>
                <th style="width:17%">IP Address</th>
                <th style="width:17%">Hostname</th>
                <th style="width:10%;text-align:center">Critical</th>
                <th style="width:8%;text-align:center">High</th>
                <th style="width:9%;text-align:center">Medium</th>
                <th style="width:7%;text-align:center">Low</th>
                <th style="width:8%;text-align:center">Total</th>
                <th style="width:9%;text-align:center">Open</th>
                <th style="width:10%;text-align:center">Closed</th>
            </tr>
        </thead>
        <tbody>
            @foreach($hostsSummary as $idx => $host)
            <tr>
                <td style="text-align:center;">{{ $idx + 1 }}</td>
                <td style="font-family:monospace;font-size:7.5pt;">{{ $host->ip_address }}</td>
                <td style="font-size:7.5pt;">{{ $host->hostname ?: '—' }}</td>
                <td style="text-align:center;" class="{{ $host->c ? 'sev-c' : '' }}">{{ $host->c ?: '0' }}</td>
                <td style="text-align:center;" class="{{ $host->h ? 'sev-h' : '' }}">{{ $host->h ?: '0' }}</td>
                <td style="text-align:center;" class="{{ $host->m ? 'sev-m' : '' }}">{{ $host->m ?: '0' }}</td>
                <td style="text-align:center;" class="sev-l">{{ $host->l ?: '0' }}</td>
                <td style="text-align:center;font-weight:bold;">{{ $host->total }}</td>
                <td style="text-align:center;" class="{{ $host->open_count ? 'open' : '' }}">{{ $host->open_count }}</td>
                <td style="text-align:center;" class="{{ $host->closed_count ? 'closed' : '' }}">{{ $host->closed_count }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="color:#64748b;font-style:italic;">No host data recorded for this assessment.</p>
    @endif
</div>

{{-- ═══════════════════  PAGE 5 · SCOPE  ═══════════════════ --}}
<div class="page-break"></div>
<div class="page">
    <div class="sec-title">2. Scope of Assessment</div>

    <p>The scope of this vulnerability assessment covers the following systems and IP ranges.
    All targets were scanned using <strong>Tenable Nessus</strong> in authenticated mode
    to ensure comprehensive detection of patch-level and configuration vulnerabilities.</p>

    @if($a->scopeEntries->count())
    <table>
        <thead>
            <tr>
                <th style="width:5%;text-align:center">No</th>
                <th style="width:17%">IP Address</th>
                <th style="width:16%">Hostname</th>
                <th style="width:20%">System Name</th>
                <th style="width:13%">Environment</th>
                <th style="width:12%">Location</th>
                <th style="width:17%">Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($a->scopeEntries as $idx => $s)
            <tr>
                <td style="text-align:center;">{{ $idx + 1 }}</td>
                <td style="font-family:monospace;font-size:7.5pt;">{{ $s->ip_address }}</td>
                <td style="font-size:7.5pt;">{{ $s->hostname ?: '—' }}</td>
                <td>{{ $s->system_name ?: '—' }}</td>
                <td>{{ $s->environment ?: '—' }}</td>
                <td>{{ $s->location ?: '—' }}</td>
                <td style="font-size:7.5pt;">{{ $s->notes ?: '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="color:#64748b;font-style:italic;">No scope entries linked to this assessment.</p>
    @endif

    <br>
    <table style="width:55%;">
        <tbody>
            <tr><td class="lbl" style="border:1px solid #e2e8f0;">Scan Type</td><td style="border:1px solid #e2e8f0;">Authenticated</td></tr>
            <tr><td class="lbl" style="border:1px solid #e2e8f0;">Tool</td><td style="border:1px solid #e2e8f0;">Tenable Nessus</td></tr>
            <tr><td class="lbl" style="border:1px solid #e2e8f0;">Total Scans</td><td style="border:1px solid #e2e8f0;">{{ $a->scans->count() }}</td></tr>
            <tr><td class="lbl" style="border:1px solid #e2e8f0;">Period</td>
                <td style="border:1px solid #e2e8f0;">{{ $a->period_start?->format('d M Y') ?? '—' }} – {{ $a->period_end?->format('d M Y') ?? '—' }}</td></tr>
        </tbody>
    </table>
</div>

{{-- ═══════════════════  PAGE 6 · ASSESSOR INFO  ═══════════════════ --}}
<div class="page-break"></div>
<div class="page">
    <div class="sec-title">3. Vulnerability Assessor Information</div>

    <table>
        <thead>
            <tr>
                <th style="width:25%">Name</th>
                <th style="width:30%">Title</th>
                <th style="width:28%">Email</th>
                <th style="width:17%">Phone</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $a->creator?->name ?? '—' }}</td>
                <td>Vulnerability Management Officer</td>
                <td style="font-size:7.5pt;">{{ $a->creator?->email ?? '—' }}</td>
                <td>—</td>
            </tr>
        </tbody>
    </table>

    <div class="subsec-title">3.1&nbsp; Vulnerability Severity Ratings</div>
    <table>
        <thead>
            <tr>
                <th style="width:14%">Risk Rating</th>
                <th style="width:42%">Description</th>
                <th style="width:16%;text-align:center">CVSS Score</th>
                <th style="width:28%">Action Required</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="sev-c">Critical</td>
                <td>Exploitable vulnerabilities allowing full system compromise or severe data breach</td>
                <td style="text-align:center;">9.0 – 10.0</td>
                <td>Immediate remediation required</td>
            </tr>
            <tr>
                <td class="sev-h">High</td>
                <td>Significant impact vulnerabilities that could lead to privilege escalation or data exposure</td>
                <td style="text-align:center;">7.0 – 8.9</td>
                <td>Fix as priority within 7 days</td>
            </tr>
            <tr>
                <td class="sev-m">Medium</td>
                <td>Moderate risk vulnerabilities that may be exploited under specific conditions</td>
                <td style="text-align:center;">4.0 – 6.9</td>
                <td>Fix within a reasonable timeframe</td>
            </tr>
            <tr>
                <td class="sev-l">Low</td>
                <td>Minor impact vulnerabilities with limited exploitation potential</td>
                <td style="text-align:center;">0.1 – 3.9</td>
                <td>Monitor or address in routine cycle</td>
            </tr>
        </tbody>
    </table>
</div>

{{-- ═══════════════════  PAGE 7+ · TECHNICAL FINDINGS  ═══════════════════ --}}
<div class="page-break"></div>
<div class="page">
    <div class="sec-title">4. Technical Findings</div>

    <p>Findings are ordered by severity (Critical → High → Medium → Low), then by CVSS score (descending).
    Vulnerabilities affecting multiple hosts are grouped to avoid duplication.</p>

    @php $secNum = 1; @endphp
    @foreach($sevOrder as $sev)
    @if(!empty($findingsBySeverity[$sev]))

    <div class="subsec-title">
        4.{{ $secNum++ }}&nbsp;
        <span class="{{ $sevClass($sev) }}">{{ $sev }}-Severity Findings</span>
        <span style="font-weight:normal;font-size:8pt;color:#64748b;">
            — {{ count($findingsBySeverity[$sev]) }} unique vulnerabilit{{ count($findingsBySeverity[$sev]) === 1 ? 'y' : 'ies' }}
        </span>
    </div>

    @foreach($findingsBySeverity[$sev] as $pid => $vuln)
    @php
        $openHosts   = collect($vuln['hosts'])->filter(fn($h) => in_array($h['tracking_status'],['New','Open','Unresolved','Reopened']))->count();
        $closedHosts = collect($vuln['hosts'])->filter(fn($h) => $h['tracking_status'] === 'Resolved')->count();
        $allIps      = collect($vuln['hosts'])->pluck('ip_address')->unique()->implode(', ');
    @endphp

    <div class="finding-title">{{ $vuln['vuln_name'] }}</div>

    {{-- Meta row ── --}}
    <table>
        <tbody>
            <tr>
                <td class="lbl" style="width:13%;border:1px solid #e2e8f0;">Severity</td>
                <td style="width:14%;border:1px solid #e2e8f0;"><span class="{{ $pillClass($vuln['severity']) }} pill">{{ $vuln['severity'] }}</span></td>
                <td class="lbl" style="width:13%;border:1px solid #e2e8f0;">CVSS Score</td>
                <td style="width:13%;border:1px solid #e2e8f0;font-weight:bold;">
                    {{ $vuln['cvss_score'] !== null ? number_format($vuln['cvss_score'], 1) : '—' }}
                </td>
                <td class="lbl" style="width:13%;border:1px solid #e2e8f0;">Plugin ID</td>
                <td style="width:34%;border:1px solid #e2e8f0;font-family:monospace;font-size:7.5pt;">{{ $vuln['plugin_id'] }}</td>
            </tr>
            @if($vuln['cve'])
            <tr>
                <td class="lbl" style="border:1px solid #e2e8f0;">CVE</td>
                <td colspan="5" style="border:1px solid #e2e8f0;font-family:monospace;font-size:7.5pt;">{{ $vuln['cve'] }}</td>
            </tr>
            @endif
            <tr>
                <td class="lbl" style="border:1px solid #e2e8f0;">Affected Hosts</td>
                <td colspan="3" style="border:1px solid #e2e8f0;font-family:monospace;font-size:7.5pt;">{{ $allIps }}</td>
                <td class="lbl" style="border:1px solid #e2e8f0;">Status</td>
                <td style="border:1px solid #e2e8f0;">
                    @if($openHosts > 0)<span class="open">Open ({{ $openHosts }})</span>@endif
                    @if($openHosts > 0 && $closedHosts > 0)&nbsp;/&nbsp;@endif
                    @if($closedHosts > 0)<span class="closed">Closed ({{ $closedHosts }})</span>@endif
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Affected hosts detail ── --}}
    <table style="margin-bottom:4px;">
        <thead>
            <tr>
                <th style="width:22%">IP Address</th>
                <th style="width:22%">Hostname</th>
                <th style="width:14%">Port</th>
                <th style="width:14%">Status</th>
                <th style="width:14%">First Seen</th>
                <th style="width:14%">Last Seen</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vuln['hosts'] as $h)
            @php $hStatus = in_array($h['tracking_status'],['New','Open','Unresolved','Reopened']) ? 'Open' : 'Closed'; @endphp
            <tr>
                <td style="font-family:monospace;font-size:7.5pt;">{{ $h['ip_address'] }}</td>
                <td style="font-size:7.5pt;">{{ $h['hostname'] ?: '—' }}</td>
                <td>{{ $h['port'] ? $h['port'].'/'.$h['protocol'] : '—' }}</td>
                <td class="{{ $hStatus === 'Open' ? 'open' : 'closed' }}">{{ $hStatus }}</td>
                <td style="font-size:7.5pt;">{{ $h['first_seen_at'] instanceof \Carbon\Carbon ? $h['first_seen_at']->format('d M Y') : ($h['first_seen_at'] ? date('d M Y', strtotime($h['first_seen_at'])) : '—') }}</td>
                <td style="font-size:7.5pt;">{{ $h['last_seen_at'] instanceof \Carbon\Carbon ? $h['last_seen_at']->format('d M Y') : ($h['last_seen_at'] ? date('d M Y', strtotime($h['last_seen_at'])) : '—') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Description & Recommendation ── --}}
    @if($vuln['description'] || $vuln['remediation_text'])
    <table style="margin-bottom:8px;">
        <tbody>
            @if($vuln['description'])
            <tr>
                <td class="lbl" style="width:15%;vertical-align:top;border:1px solid #e2e8f0;">Description</td>
                <td style="border:1px solid #e2e8f0;word-wrap:break-word;overflow-wrap:break-word;font-size:7.5pt;line-height:1.4;">{{ $vuln['description'] }}</td>
            </tr>
            @endif
            @if($vuln['remediation_text'])
            <tr>
                <td class="lbl" style="vertical-align:top;border:1px solid #e2e8f0;">Recommendation</td>
                <td style="border:1px solid #e2e8f0;word-wrap:break-word;overflow-wrap:break-word;font-size:7.5pt;line-height:1.4;">{{ $vuln['remediation_text'] }}</td>
            </tr>
            @endif
        </tbody>
    </table>
    @endif

    @endforeach

    @endif
    @endforeach

</div>{{-- end .page --}}

</body>
</html>
