@php
    $kri = $kri ?? [];
    $severityChart = [
        ['Critical', (int) ($stats->critical ?? 0), '#780000'],
        ['High', (int) ($stats->high ?? 0), '#dc0000'],
        ['Medium', (int) ($stats->medium ?? 0), '#fd8c00'],
        ['Low', (int) ($stats->low ?? 0), '#16a34a'],
    ];
    $workflowChart = [
        ['Open', $kri['open_remediation'] ?? 0, '#dc2626'],
        ['In Progress', $kri['in_progress'] ?? 0, '#d97706'],
        ['Accepted', $kri['accepted_risk'] ?? 0, '#64748b'],
        ['Resolved', $kri['resolved_by_scan'] ?? 0, '#16a34a'],
    ];
    $slaChart = [
        ['Breached', $kri['sla_breached'] ?? 0, '#dc2626'],
        ['Approaching', $kri['sla_approaching'] ?? 0, '#d97706'],
        ['On Track', $kri['sla_on_track'] ?? 0, '#16a34a'],
        ['Met', $kri['sla_met'] ?? 0, '#0ea5e9'],
    ];
    $chartRows = function (array $rows): string {
        $max = max(1, collect($rows)->max(fn($row) => (int) $row[1]));
        $html = '';
        foreach ($rows as [$label, $value, $color]) {
            $width = max(2, round(((int) $value / $max) * 100));
            $html .= '<tr><td class="bar-label">' . e($label) . '</td><td><div class="bar-track"><div class="bar-fill" style="width:' . $width . '%;background:' . $color . '"></div></div></td><td class="bar-value">' . number_format($value) . '</td></tr>';
        }
        return $html;
    };
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>KRI Report</title>
    <style>
        body { font-family: Arial, sans-serif; color:#0f172a; margin:0; background:#fff; }
        .slide { width:10in; height:5.625in; padding:.38in .45in; box-sizing:border-box; page-break-after:always; background:#f8fafc; }
        .slide:last-child { page-break-after:auto; }
        h1 { margin:0 0 .08in; font-size:30pt; color:#0f172a; }
        h2 { margin:0 0 .18in; font-size:22pt; color:#0f172a; }
        .sub { color:#64748b; font-size:11pt; margin-bottom:.24in; }
        .grid { width:100%; border-collapse:separate; border-spacing:.12in; }
        .tile { background:#fff; border:1px solid #dbe3ef; border-radius:8px; padding:.16in; vertical-align:top; }
        .label { color:#64748b; font-size:9pt; font-weight:bold; text-transform:uppercase; letter-spacing:.5px; }
        .value { font-size:26pt; font-weight:bold; margin:.08in 0; }
        .note { color:#64748b; font-size:10pt; }
        .bar-table { width:100%; border-collapse:collapse; margin-top:.08in; }
        .bar-table td { padding:.07in .05in; vertical-align:middle; }
        .bar-label { width:1.25in; font-weight:bold; color:#475569; font-size:10pt; }
        .bar-track { height:.14in; background:#e2e8f0; border-radius:9px; overflow:hidden; }
        .bar-fill { height:.14in; border-radius:9px; }
        .bar-value { width:.55in; text-align:right; color:#475569; font-weight:bold; font-size:10pt; }
        .data-table { width:100%; border-collapse:collapse; background:#fff; }
        .data-table th { background:#e2e8f0; color:#475569; text-align:left; font-size:9pt; padding:.08in; }
        .data-table td { border-bottom:1px solid #e2e8f0; padding:.08in; font-size:9pt; }
    </style>
</head>
<body>
    <div class="slide">
        <h1>Vulnerability KRI Report</h1>
        <div class="sub">{{ $assessment->name }} &middot; Generated {{ now()->format('d M Y, H:i') }}</div>
        <table class="grid">
            <tr>
                <td class="tile"><div class="label">Risk Score</div><div class="value" style="color:#780000">{{ number_format($kri['risk_score']) }}</div><div class="note">Weighted active severity score</div></td>
                <td class="tile"><div class="label">Critical / High</div><div class="value" style="color:#dc0000">{{ number_format($kri['critical_high']) }}</div><div class="note">{{ $kri['critical_high_pct'] }}% of active findings</div></td>
                <td class="tile"><div class="label">SLA Breached</div><div class="value" style="color:#dc2626">{{ number_format($kri['sla_breached']) }}</div><div class="note">{{ number_format($kri['sla_approaching']) }} approaching</div></td>
            </tr>
            <tr>
                <td class="tile"><div class="label">Remediation</div><div class="value" style="color:#16a34a">{{ $kri['remediation_pct'] }}%</div><div class="note">{{ number_format($kri['resolved_by_scan']) }} resolved</div></td>
                <td class="tile"><div class="label">Active Hosts</div><div class="value" style="color:#1d4ed8">{{ number_format($kri['active_hosts']) }}</div><div class="note">{{ number_format($kri['mission_critical_hosts']) }} mission-critical</div></td>
                <td class="tile"><div class="label">Accepted Risk</div><div class="value" style="color:#64748b">{{ number_format($kri['accepted_risk']) }}</div><div class="note">Workflow exceptions</div></td>
            </tr>
        </table>
    </div>

    <div class="slide">
        <h2>KRI Charts</h2>
        <table class="grid">
            <tr>
                <td class="tile"><div class="label">Active Severity Distribution</div><table class="bar-table">{!! $chartRows($severityChart) !!}</table></td>
                <td class="tile"><div class="label">Remediation Workflow</div><table class="bar-table">{!! $chartRows($workflowChart) !!}</table></td>
            </tr>
            <tr>
                <td class="tile"><div class="label">SLA Health</div><table class="bar-table">{!! $chartRows($slaChart) !!}</table></td>
                <td class="tile"><div class="label">Report Context</div><div class="note" style="font-size:12pt;margin-top:.12in">SLA Policy: {{ $kri['sla_policy'] ?: 'No SLA policy configured' }}<br>Scan Count: {{ $assessment->scans->count() }}<br>Latest Data: {{ $activeScan?->created_at?->format('d M Y, H:i') ?? '-' }}</div></td>
            </tr>
        </table>
    </div>

    <div class="slide">
        <h2>Highest Risk Hosts</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>IP Address</th>
                    <th>Hostname</th>
                    <th>Critical</th>
                    <th>High</th>
                    <th>Active</th>
                    <th>Owner</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topIps->take(8) as $ip)
                <tr>
                    <td>{{ $ip->ip_address }}</td>
                    <td>{{ $ip->hostname ?: '-' }}</td>
                    <td style="color:#780000;font-weight:bold">{{ number_format($ip->critical) }}</td>
                    <td style="color:#dc0000;font-weight:bold">{{ number_format($ip->high) }}</td>
                    <td style="color:#059669;font-weight:bold">{{ number_format($ip->active_count) }}</td>
                    <td>{{ $ip->system_owner ?: '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
