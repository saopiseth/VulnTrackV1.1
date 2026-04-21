<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:w="urn:schemas-microsoft-com:office:word"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta charset="utf-8">
<meta name=ProgId content=Word.Document>
<meta name=Generator content="Microsoft Word 15">
<style>
    /* ── Base ── */
    body  { font-family: Calibri, sans-serif; font-size: 11pt; color: #1e293b; margin: 2.54cm 2.54cm; line-height: 1.4; }

    /* ── Headings ── */
    h1  { font-size: 26pt; font-weight: bold; color: #0f172a; margin: 0 0 6pt; }
    h2  { font-size: 14pt; font-weight: bold; color: #0f172a; margin: 20pt 0 6pt;
          border-bottom: 2pt solid #84cc16; padding-bottom: 3pt; }
    h3  { font-size: 12pt; font-weight: bold; color: #1e293b; margin: 14pt 0 5pt; }
    h4  { font-size: 11pt; font-weight: bold; color: #0f172a; margin: 12pt 0 4pt;
          background: #f8fafc; padding: 4pt 8pt; border-left: 3pt solid #84cc16; }
    p   { margin: 4pt 0 8pt; }

    /* ── Tables ── */
    table { width: 100%; border-collapse: collapse; margin-bottom: 10pt; font-size: 10pt; }
    th    { background: #1e293b; color: #ffffff; padding: 5pt 8pt; font-size: 9pt; text-align: left; }
    td    { padding: 4pt 8pt; border: 1pt solid #e2e8f0; vertical-align: top; }
    .lbl  { background: #f1f5f9; font-weight: bold; color: #475569; width: 22%; font-size: 9.5pt; }
    tr:nth-child(even) td { background: #f8fafc; }

    /* ── Severity colours ── */
    .sev-c  { color: #991b1b; font-weight: bold; }
    .sev-h  { color: #c2410c; font-weight: bold; }
    .sev-m  { color: #854d0e; font-weight: bold; }
    .sev-l  { color: #475569; }

    /* ── Status ── */
    .open   { color: #dc2626; font-weight: bold; }
    .closed { color: #16a34a; font-weight: bold; }

    /* ── Cover label ── */
    .cover-label {
        font-size: 9pt; font-weight: bold; color: #84cc16;
        text-transform: uppercase; letter-spacing: 1pt; margin-bottom: 8pt;
    }
    .cover-sub { font-size: 10pt; color: #64748b; margin: 4pt 0 16pt; }

    /* ── TOC ── */
    .toc-1  { margin: 5pt 0; font-size: 11pt; font-weight: bold; }
    .toc-2  { margin: 2pt 0 2pt 18pt; font-size: 10pt; color: #475569; }

    /* ── Confidential / footer ── */
    .conf   { text-align: center; color: #94a3b8; font-size: 8pt;
              margin-top: 24pt; border-top: 1pt solid #e2e8f0; padding-top: 6pt; }

    /* ── Page break ── */
    .pb { page-break-before: always; }
</style>
</head>
<body>

@php
    $sevOrder   = ['Critical', 'High', 'Medium', 'Low'];
    $totalActive   = collect($sevOrder)->sum(fn($s) => $active[$s]   ?? 0);
    $totalResolved = collect($sevOrder)->sum(fn($s) => $resolved[$s] ?? 0);
    $sevClass   = fn($s) => match($s) { 'Critical'=>'sev-c','High'=>'sev-h','Medium'=>'sev-m',default=>'sev-l' };
@endphp

{{-- ═══════════════════════════  PAGE 1 · COVER  ═══════════════════════════ --}}
<div style="text-align:center; padding: 60pt 0 40pt;">
    <div class="cover-label">Confidential — Internal Use Only</div>
    <h1 style="font-size:28pt; margin-bottom:8pt;">Vulnerability Assessment Report</h1>
    <p style="font-size:14pt; color:#475569; margin-bottom:28pt;">{{ $a->name }}</p>

    <table style="width:60%; margin:0 auto 24pt; text-align:left; border:none;">
        <tr>
            <td class="lbl" style="width:40%; border:none;">Organization</td>
            <td style="border:none; font-weight:bold;">{{ config('app.name', 'Wing Bank') }}</td>
        </tr>
        <tr>
            <td class="lbl" style="border:none;">Assessment Period</td>
            <td style="border:none;">
                {{ $a->period_start?->format('d F Y') ?? '—' }} –
                {{ $a->period_end?->format('d F Y') ?? '—' }}
            </td>
        </tr>
        <tr>
            <td class="lbl" style="border:none;">Environment</td>
            <td style="border:none;">{{ $a->environment ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl" style="border:none;">Prepared By</td>
            <td style="border:none;">{{ $a->creator?->name ?? 'Vulnerability Management Team' }}</td>
        </tr>
        <tr>
            <td class="lbl" style="border:none;">Tool Used</td>
            <td style="border:none;">Tenable Nessus</td>
        </tr>
        <tr>
            <td class="lbl" style="border:none;">Report Date</td>
            <td style="border:none;">{{ now()->format('d F Y') }}</td>
        </tr>
    </table>

    <p style="font-size:9pt; color:#94a3b8; border-top:1pt solid #e2e8f0; padding-top:10pt; margin-top:40pt;">
        This document contains confidential and proprietary information. It is intended solely for
        authorised personnel. Any reproduction, distribution, or disclosure without prior written
        approval is strictly prohibited.
    </p>
</div>

{{-- ═══════════════════  PAGE 2 · DOCUMENT INFO & APPROVAL  ═══════════════════ --}}
<div class="pb"></div>
<h2>Document Information</h2>
<table>
    <tbody>
        <tr><td class="lbl">Document Title</td><td>Vulnerability Assessment Report — {{ $a->name }}</td></tr>
        <tr><td class="lbl">Version</td><td>1.0</td></tr>
        <tr><td class="lbl">Date</td><td>{{ now()->format('d F Y') }}</td></tr>
        <tr><td class="lbl">Prepared By</td><td>{{ $a->creator?->name ?? 'Vulnerability Management Team' }}</td></tr>
        <tr><td class="lbl">Reviewed By</td><td>&nbsp;</td></tr>
        <tr><td class="lbl">Classification</td><td>Confidential — Internal Use Only</td></tr>
    </tbody>
</table>

<h2 style="margin-top:18pt;">Approval Information</h2>
<table>
    <thead>
        <tr>
            <th style="width:25%">Name</th>
            <th style="width:30%">Title</th>
            <th style="width:25%">Signature</th>
            <th style="width:20%">Date</th>
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

{{-- ══════════════════════════  PAGE 3 · TABLE OF CONTENTS  ══════════════════════════ --}}
<div class="pb"></div>
<h2 style="border-bottom:none;">Table of Contents</h2>
<br>
<div class="toc-1">1.&nbsp;&nbsp; Executive Summary</div>
<div class="toc-2">1.1&nbsp;&nbsp; Vulnerability Summary</div>
<div class="toc-2">1.2&nbsp;&nbsp; Vulnerability by Host</div>
<div class="toc-1" style="margin-top:4pt;">2.&nbsp;&nbsp; Scope of Assessment</div>
<div class="toc-1" style="margin-top:4pt;">3.&nbsp;&nbsp; Vulnerability Assessor Information</div>
<div class="toc-2">3.1&nbsp;&nbsp; Vulnerability Severity Ratings</div>
<div class="toc-1" style="margin-top:4pt;">4.&nbsp;&nbsp; Technical Findings</div>
@php $si = 1; @endphp
@foreach($sevOrder as $sev)
@if(!empty($findingsBySeverity[$sev]))
<div class="toc-2">4.{{ $si++ }}&nbsp;&nbsp; {{ $sev }}-Severity Findings ({{ count($findingsBySeverity[$sev]) }} unique)</div>
@endif
@endforeach

{{-- ══════════════════════════  PAGE 4 · EXECUTIVE SUMMARY  ══════════════════════════ --}}
<div class="pb"></div>
<h2>1. Executive Summary</h2>

<p>This {{ $a->environment ?? '' }} Vulnerability Assessment (VA) was conducted to evaluate the current security posture
by identifying, analysing, and prioritising vulnerabilities across critical assets including servers,
applications, and network devices within the scope defined below.</p>

<p>The assessment was performed during the period
<strong>{{ $a->period_start?->format('d F Y') ?? '—' }}</strong> to
<strong>{{ $a->period_end?->format('d F Y') ?? '—' }}</strong>
using Tenable Nessus in {{ $a->scans->count() > 0 ? 'authenticated' : 'unauthenticated' }} scan mode
across {{ $hostsSummary->count() }} host(s).
A total of <strong>{{ $totalActive }}</strong> active finding(s) and
<strong>{{ $totalResolved }}</strong> resolved finding(s) were identified.</p>

<h3>1.1&nbsp;&nbsp; Vulnerability Summary</h3>
<table>
    <thead>
        <tr>
            <th style="width:30%">Severity</th>
            <th style="width:17%;text-align:center">Total</th>
            <th style="width:17%;text-align:center">Open</th>
            <th style="width:17%;text-align:center">Closed</th>
            <th style="width:19%;text-align:center">CVSS Range</th>
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
            <td class="{{ $sevClass($sev) }}">{{ $sev }}</td>
            <td style="text-align:center;font-weight:bold;">{{ $total }}</td>
            <td style="text-align:center;" class="{{ $openCnt ? 'open' : '' }}">{{ $openCnt }}</td>
            <td style="text-align:center;" class="{{ $closeCnt ? 'closed' : '' }}">{{ $closeCnt }}</td>
            <td style="text-align:center;color:#64748b;font-size:9pt;">{{ $range }}</td>
        </tr>
        @endforeach
        <tr style="font-weight:bold; background:#f1f5f9;">
            <td>Total</td>
            <td style="text-align:center;">{{ $totalActive + $totalResolved }}</td>
            <td style="text-align:center;" class="{{ $totalActive ? 'open' : '' }}">{{ $totalActive }}</td>
            <td style="text-align:center;" class="{{ $totalResolved ? 'closed' : '' }}">{{ $totalResolved }}</td>
            <td></td>
        </tr>
    </tbody>
</table>

<h3>1.2&nbsp;&nbsp; Vulnerability by Host</h3>
@if($hostsSummary->count())
<table>
    <thead>
        <tr>
            <th style="width:5%;text-align:center">No</th>
            <th style="width:18%">IP Address</th>
            <th style="width:18%">Hostname</th>
            <th style="width:10%;text-align:center">Critical</th>
            <th style="width:8%;text-align:center">High</th>
            <th style="width:10%;text-align:center">Medium</th>
            <th style="width:8%;text-align:center">Low</th>
            <th style="width:8%;text-align:center">Total</th>
            <th style="width:10%;text-align:center">Open</th>
            <th style="width:10%;text-align:center">Closed</th>
        </tr>
    </thead>
    <tbody>
        @foreach($hostsSummary as $idx => $host)
        <tr>
            <td style="text-align:center;">{{ $idx + 1 }}</td>
            <td style="font-family:Consolas,monospace;font-size:9.5pt;">{{ $host->ip_address }}</td>
            <td>{{ $host->hostname ?: '—' }}</td>
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

{{-- ══════════════════════════  PAGE 5 · SCOPE  ══════════════════════════ --}}
<div class="pb"></div>
<h2>2. Scope of Assessment</h2>

<p>The scope of this vulnerability assessment covers the following systems and IP ranges.
All targets were scanned using <strong>Tenable Nessus</strong> in authenticated mode
to ensure comprehensive detection of patch-level and configuration vulnerabilities.</p>

@if($a->scopeEntries->count())
<table>
    <thead>
        <tr>
            <th style="width:5%;text-align:center">No</th>
            <th style="width:18%">IP Address</th>
            <th style="width:18%">Hostname</th>
            <th style="width:20%">System Name</th>
            <th style="width:12%">Environment</th>
            <th style="width:12%">Location</th>
            <th style="width:15%">Notes</th>
        </tr>
    </thead>
    <tbody>
        @foreach($a->scopeEntries as $idx => $s)
        <tr>
            <td style="text-align:center;">{{ $idx + 1 }}</td>
            <td style="font-family:Consolas,monospace;font-size:9.5pt;">{{ $s->ip_address }}</td>
            <td>{{ $s->hostname ?: '—' }}</td>
            <td>{{ $s->system_name ?: '—' }}</td>
            <td>{{ $s->environment ?: '—' }}</td>
            <td>{{ $s->location ?: '—' }}</td>
            <td>{{ $s->notes ?: '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<p style="color:#64748b;font-style:italic;">No scope entries linked to this assessment.</p>
@endif

<p><strong>Scan Details:</strong></p>
<table style="width:60%;">
    <tbody>
        <tr><td class="lbl">Scan Type</td><td>Authenticated</td></tr>
        <tr><td class="lbl">Tool</td><td>Tenable Nessus</td></tr>
        <tr><td class="lbl">Total Scans</td><td>{{ $a->scans->count() }}</td></tr>
        <tr><td class="lbl">Period</td><td>{{ $a->period_start?->format('d M Y') ?? '—' }} – {{ $a->period_end?->format('d M Y') ?? '—' }}</td></tr>
    </tbody>
</table>

{{-- ══════════════════════  PAGE 6 · ASSESSOR INFO  ══════════════════════ --}}
<div class="pb"></div>
<h2>3. Vulnerability Assessor Information</h2>

<table>
    <thead>
        <tr>
            <th style="width:25%">Name</th>
            <th style="width:30%">Title</th>
            <th style="width:25%">Email</th>
            <th style="width:20%">Phone</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ $a->creator?->name ?? '—' }}</td>
            <td>Vulnerability Management Officer</td>
            <td style="font-size:9.5pt;">{{ $a->creator?->email ?? '—' }}</td>
            <td>—</td>
        </tr>
    </tbody>
</table>

<h3>3.1&nbsp;&nbsp; Vulnerability Severity Ratings</h3>
<table>
    <thead>
        <tr>
            <th style="width:15%">Risk Rating</th>
            <th style="width:40%">Description</th>
            <th style="width:18%">CVSS Score</th>
            <th style="width:27%">Action Required</th>
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

{{-- ══════════════════════  PAGE 7+ · TECHNICAL FINDINGS  ══════════════════════ --}}
<div class="pb"></div>
<h2>4. Technical Findings</h2>

<p>Findings are ordered by severity (Critical → High → Medium → Low), then by CVSS score (descending).
Vulnerabilities affecting multiple hosts are grouped together to avoid duplication.</p>

@php $secNum = 1; @endphp
@foreach($sevOrder as $sev)
@if(!empty($findingsBySeverity[$sev]))

<h3>4.{{ $secNum++ }}&nbsp;&nbsp; {{ $sev }}-Severity Findings
    <span style="font-weight:normal;font-size:10pt;color:#64748b;">
        ({{ count($findingsBySeverity[$sev]) }} unique vulnerabilities)
    </span>
</h3>

@foreach($findingsBySeverity[$sev] as $pid => $vuln)
@php
    $openHosts   = collect($vuln['hosts'])->filter(fn($h) => in_array($h['tracking_status'],['New','Open','Unresolved','Reopened']))->count();
    $closedHosts = collect($vuln['hosts'])->filter(fn($h) => $h['tracking_status'] === 'Resolved')->count();
    $allIps      = collect($vuln['hosts'])->pluck('ip_address')->unique()->implode(', ');
    $firstSeen   = collect($vuln['hosts'])->pluck('first_seen_at')->filter()->min();
    $sevCls      = $sevClass($vuln['severity']);
@endphp

<h4>{{ $vuln['vuln_name'] }}</h4>

<table class="detail-table">
    <tbody>
        <tr>
            <td class="lbl" style="width:18%">Severity</td>
            <td class="{{ $sevCls }}" style="width:15%">{{ $vuln['severity'] }}</td>
            <td class="lbl" style="width:15%">CVSS Score</td>
            <td style="width:15%;font-weight:bold;">
                {{ $vuln['cvss_score'] !== null ? number_format($vuln['cvss_score'], 1) : '—' }}
            </td>
            <td class="lbl" style="width:15%">Plugin ID</td>
            <td style="width:22%;font-family:Consolas,monospace;font-size:9pt;">{{ $vuln['plugin_id'] }}</td>
        </tr>
        @if($vuln['cve'])
        <tr>
            <td class="lbl">CVE</td>
            <td colspan="5" style="font-size:9.5pt;font-family:Consolas,monospace;">{{ $vuln['cve'] }}</td>
        </tr>
        @endif
        <tr>
            <td class="lbl">Affected Hosts</td>
            <td colspan="3" style="font-family:Consolas,monospace;font-size:9.5pt;">{{ $allIps }}</td>
            <td class="lbl">Status</td>
            <td>
                @if($openHosts > 0)<span class="open">Open ({{ $openHosts }})</span>@endif
                @if($openHosts > 0 && $closedHosts > 0) &nbsp;/&nbsp; @endif
                @if($closedHosts > 0)<span class="closed">Closed ({{ $closedHosts }})</span>@endif
            </td>
        </tr>
    </tbody>
</table>

{{-- Host detail table --}}
<table style="font-size:9pt; margin-bottom:6pt;">
    <thead>
        <tr>
            <th style="width:20%">IP Address</th>
            <th style="width:20%">Hostname</th>
            <th style="width:12%">Port</th>
            <th style="width:15%">Status</th>
            <th style="width:18%">First Seen</th>
            <th style="width:15%">Last Seen</th>
        </tr>
    </thead>
    <tbody>
        @foreach($vuln['hosts'] as $h)
        @php $hStatus = in_array($h['tracking_status'],['New','Open','Unresolved','Reopened']) ? 'Open' : 'Closed'; @endphp
        <tr>
            <td style="font-family:Consolas,monospace;">{{ $h['ip_address'] }}</td>
            <td>{{ $h['hostname'] ?: '—' }}</td>
            <td>{{ $h['port'] ? $h['port'].'/'.$h['protocol'] : '—' }}</td>
            <td class="{{ $hStatus === 'Open' ? 'open' : 'closed' }}">{{ $hStatus }}</td>
            <td>{{ $h['first_seen_at'] instanceof \Carbon\Carbon ? $h['first_seen_at']->format('d M Y') : ($h['first_seen_at'] ? date('d M Y', strtotime($h['first_seen_at'])) : '—') }}</td>
            <td>{{ $h['last_seen_at'] instanceof \Carbon\Carbon ? $h['last_seen_at']->format('d M Y') : ($h['last_seen_at'] ? date('d M Y', strtotime($h['last_seen_at'])) : '—') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

@if($vuln['description'])
<table style="margin-bottom:4pt;">
    <tbody>
        <tr>
            <td class="lbl" style="width:18%;vertical-align:top;">Description</td>
            <td style="white-space:pre-wrap;font-size:9.5pt;">{{ $vuln['description'] }}</td>
        </tr>
        @if($vuln['remediation_text'])
        <tr>
            <td class="lbl" style="vertical-align:top;">Recommendation</td>
            <td style="white-space:pre-wrap;font-size:9.5pt;">{{ $vuln['remediation_text'] }}</td>
        </tr>
        @endif
    </tbody>
</table>
@elseif($vuln['remediation_text'])
<table style="margin-bottom:4pt;">
    <tbody>
        <tr>
            <td class="lbl" style="width:18%;vertical-align:top;">Recommendation</td>
            <td style="white-space:pre-wrap;font-size:9.5pt;">{{ $vuln['remediation_text'] }}</td>
        </tr>
    </tbody>
</table>
@endif

@endforeach
{{-- space between severity sections --}}
<br>
@endif
@endforeach

<div class="conf">
    {{ $a->name }} &mdash; Vulnerability Assessment Report &mdash;
    Generated {{ now()->format('d M Y H:i') }} &mdash; CONFIDENTIAL
</div>

</body>
</html>
