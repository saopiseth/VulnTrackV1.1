@extends('layouts.app')
@section('title', $assetInventory->ip_address . ' — Asset Detail')

@section('content')
<style>
    .detail-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:1.35rem 1.5rem; margin-bottom:1.25rem; }
    .section-label { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.7px;
        color:var(--primary-dark); margin-bottom:.85rem; padding-bottom:.45rem;
        border-bottom:2px solid var(--primary); display:flex; align-items:center; gap:.4rem; }
    .kv-row { display:flex; gap:.5rem; margin-bottom:.6rem; font-size:.875rem; }
    .kv-row .kv-label { color:#64748b; font-weight:600; min-width:130px; flex-shrink:0; }
    .kv-row .kv-val   { color:#0f172a; word-break:break-all; }
    .vuln-big { border-radius:12px; padding:1rem 1.25rem; text-align:center; }
    .vuln-big .v-num  { font-size:2rem; font-weight:800; line-height:1.1; }
    .vuln-big .v-lbl  { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; margin-top:.2rem; }
    .badge-scope { padding:.2rem .6rem; border-radius:20px; font-size:.68rem; font-weight:700; display:inline-block; }
    .sev-badge { padding:.18rem .55rem; border-radius:20px; font-size:.72rem; font-weight:700; display:inline-block; }
    .sev-Critical { background:#fee2e2; color:#991b1b; }
    .sev-High     { background:#fef3c7; color:#92400e; }
    .sev-Medium   { background:#e0f2fe; color:#0c4a6e; }
    .sev-Low      { background:#f0fdf4; color:#166534; }
    .sev-Info     { background:#f1f5f9; color:#475569; }
    .tbl th { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px;
              color:#64748b; border-bottom:2px solid #e2e8f0; white-space:nowrap; }
    .tbl td { font-size:.83rem; vertical-align:middle; border-bottom:1px solid #f1f5f9; }
    .tbl tr:hover td { background:#fafafa; }
    .os-history-row { background:#f8fafc; border-radius:8px; padding:.65rem .85rem; margin-bottom:.5rem; font-size:.82rem; }
    .port-pill { display:inline-block; background:#f1f5f9; color:#374151; border-radius:5px;
                 font-size:.73rem; font-family:monospace; padding:.1rem .4rem; margin:1px; }
</style>

{{-- Breadcrumb --}}
<div class="page-header">
    <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('asset-inventory.index') }}">Asset Inventory</a></li>
        <li class="breadcrumb-item active">{{ $assetInventory->ip_address }}</li>
    </ol></nav>
    <h4 class="mb-0">
        <i class="bi bi-pc-display-horizontal me-2" style="color:var(--primary)"></i>
        {{ $assetInventory->ip_address }}
        @if($assetInventory->hostname)
            <span style="font-weight:400;color:#64748b;font-size:1rem"> — {{ $assetInventory->hostname }}</span>
        @endif
    </h4>
</div>

{{-- Vuln Summary Strip --}}
<div class="row g-3 mb-3">
    @foreach([
        ['Critical', $assetInventory->vuln_critical, '#fee2e2','#991b1b'],
        ['High',     $assetInventory->vuln_high,     '#fef3c7','#92400e'],
        ['Medium',   $assetInventory->vuln_medium,   '#e0f2fe','#0c4a6e'],
        ['Low',      $assetInventory->vuln_low,      '#f0fdf4','#166534'],
    ] as [$sev, $cnt, $bg, $col])
    <div class="col-6 col-md-3">
        <div class="vuln-big" style="background:{{ $bg }}">
            <div class="v-num" style="color:{{ $col }}">{{ number_format($cnt) }}</div>
            <div class="v-lbl" style="color:{{ $col }}">{{ $sev }}</div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-3">
    {{-- Left column: Asset details + OS --}}
    <div class="col-lg-5">

        {{-- Asset Details --}}
        <div class="detail-card">
            <div class="section-label"><i class="bi bi-info-circle-fill"></i> Asset Details</div>
            <div class="kv-row">
                <span class="kv-label">IP Address</span>
                <span class="kv-val"><code style="background:#f1f5f9;padding:.1rem .4rem;border-radius:5px">{{ $assetInventory->ip_address }}</code></span>
            </div>
            <div class="kv-row">
                <span class="kv-label">Hostname</span>
                <span class="kv-val">{{ $assetInventory->hostname ?? '—' }}</span>
            </div>
            <div class="kv-row">
                <span class="kv-label">System Name</span>
                <span class="kv-val">{{ $assetInventory->system_name ?? '—' }}</span>
            </div>
            <div class="kv-row">
                <span class="kv-label">Status</span>
                <span class="kv-val">
                    @php $stColors=['Active'=>['#dcfce7','#166534'],'Inactive'=>['#f1f5f9','#475569'],'Decommissioned'=>['#fee2e2','#991b1b']];
                    [$stbg,$stcol]=$stColors[$assetInventory->status]??['#f1f5f9','#475569']; @endphp
                    <span class="badge-scope" style="background:{{ $stbg }};color:{{ $stcol }}">{{ $assetInventory->status }}</span>
                </span>
            </div>
            <div class="kv-row">
                <span class="kv-label">Scope</span>
                <span class="kv-val">
                    @if($assetInventory->identified_scope)
                    @php $scopeColors=['PCI'=>['#fef2f2','#991b1b'],'Swift'=>['#ecfeff','#0e7490'],
                        'Non-Bank'=>['#f0fdf4','#166534'],'Public'=>['#fffbeb','#92400e'],
                        'Critical'=>['#fff1f2','#be123c'],'Less Critical'=>['#faf5ff','#6d28d9']];
                    [$sbg,$scol]=$scopeColors[$assetInventory->identified_scope]??['#f1f5f9','#475569']; @endphp
                    <span class="badge-scope" style="background:{{ $sbg }};color:{{ $scol }}">{{ $assetInventory->identified_scope }}</span>
                    @else —@endif
                </span>
            </div>
            <div class="kv-row">
                <span class="kv-label">Environment</span>
                <span class="kv-val">
                    @if($assetInventory->environment)
                    @php $envColors=['PROD'=>['#fee2e2','#991b1b'],'UAT'=>['#fef9c3','#854d0e'],'STAGE'=>['#f1f5f9','#475569'],
                        'DR'=>['#fff7ed','#9a3412'],'DEV'=>['#ecfeff','#0e7490'],
                        'Non-Prod'=>['#eef2ff','#3730a3'],'DEV-QA'=>['#fdf4ff','#7e22ce']];
                    [$ebg,$ecol]=$envColors[$assetInventory->environment]??['#f1f5f9','#475569']; @endphp
                    <span class="badge-scope" style="background:{{ $ebg }};color:{{ $ecol }}">{{ $assetInventory->environment }}</span>
                    @else —@endif
                </span>
            </div>
            <div class="kv-row">
                <span class="kv-label">Last Scanned</span>
                <span class="kv-val">{{ $assetInventory->last_scanned_at?->format('d M Y, H:i') ?? '—' }}</span>
            </div>
            @if(count($assetInventory->openPortsArray()))
            <div class="kv-row">
                <span class="kv-label">Open Ports</span>
                <span class="kv-val">
                    @foreach($assetInventory->openPortsArray() as $p)
                        <span class="port-pill">{{ $p }}</span>
                    @endforeach
                </span>
            </div>
            @endif
        </div>

        {{-- OS Information --}}
        <div class="detail-card">
            @php $badge = $assetInventory->osFamilyBadge(); @endphp
            <div class="section-label"><i class="bi bi-cpu-fill"></i> Operating System</div>
            <div class="kv-row">
                <span class="kv-label">OS Family</span>
                <span class="kv-val">
                    @if($assetInventory->os_family)
                    <span style="background:{{ $badge['bg'] }};color:{{ $badge['color'] }};padding:.2rem .55rem;border-radius:7px;font-size:.78rem;font-weight:700">
                        <i class="bi {{ $badge['icon'] }}"></i> {{ $assetInventory->os_family }}
                    </span>
                    @else —@endif
                </span>
            </div>
            <div class="kv-row">
                <span class="kv-label">OS Name</span>
                <span class="kv-val" style="font-size:.83rem">{{ $assetInventory->os ?? '—' }}</span>
            </div>
            <div class="kv-row">
                <span class="kv-label">Kernel / Build</span>
                <span class="kv-val">
                    @if($assetInventory->os_kernel)
                        <code style="background:#f1f5f9;padding:.15rem .5rem;border-radius:5px;font-size:.8rem">{{ $assetInventory->os_kernel }}</code>
                    @else —@endif
                </span>
            </div>

            {{-- Per-assessment OS detection history --}}
            @if($osHistory->isNotEmpty())
            <div class="section-label mt-3"><i class="bi bi-clock-history"></i> Detection History</div>
            @foreach($osHistory as $h)
            <div class="os-history-row">
                <div style="font-weight:600;color:#0f172a;margin-bottom:.25rem">
                    {{ $h->effectiveOsName() ?? 'Unknown' }}
                    @if($h->hasOverride())
                        <span style="background:#fdf4ff;color:#7e22ce;font-size:.65rem;font-weight:700;padding:.1rem .4rem;border-radius:5px;margin-left:4px">OVERRIDE</span>
                    @endif
                </div>
                @if($h->os_kernel)
                <div style="color:#475569;font-size:.78rem">
                    <i class="bi bi-terminal me-1"></i>
                    <code style="font-size:.76rem">{{ $h->os_kernel }}</code>
                </div>
                @endif
                <div style="color:#94a3b8;font-size:.75rem;margin-top:.2rem">
                    {{ $h->assessment?->name ?? 'Unknown assessment' }}
                    · Confidence {{ $h->os_confidence }}%
                    · {{ $h->updated_at?->format('d M Y') }}
                </div>
            </div>
            @endforeach
            @endif
        </div>

        {{-- Assessments --}}
        @if($assessments->isNotEmpty())
        <div class="detail-card">
            <div class="section-label"><i class="bi bi-folder2-open"></i> Seen In Assessments</div>
            @foreach($assessments as $a)
            <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem">
                <i class="bi bi-bug-fill" style="color:var(--primary);font-size:.85rem"></i>
                <a href="{{ route('vuln-assessments.show', $a->id) }}"
                   style="color:var(--primary-dark);text-decoration:none;font-size:.85rem;font-weight:500">{{ $a->name }}</a>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Right column: Findings --}}
    <div class="col-lg-7">
        <div class="detail-card" style="padding-bottom:0">
            <div class="section-label">
                <i class="bi bi-shield-exclamation"></i> Vulnerabilities
                <span style="background:color-mix(in srgb,var(--primary) 20%,white);color:var(--primary-dark);
                      font-size:.65rem;padding:.1rem .45rem;border-radius:10px;margin-left:auto">
                    {{ $findings->count() }} shown
                    @if($findings->count() === 100)(top 100)@endif
                </span>
            </div>

            @if($findings->isEmpty())
                <div class="text-center py-4 text-muted"><i class="bi bi-check-circle" style="font-size:1.5rem"></i><p class="mt-1 mb-0">No findings for this asset.</p></div>
            @else
            <div class="table-responsive" style="margin:0 -1.5rem">
                <table class="table tbl mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Vulnerability</th>
                            <th>Severity</th>
                            <th>CVSS</th>
                            <th>Port</th>
                            <th>CVE</th>
                            <th class="pe-4">Assessment</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($findings as $f)
                    <tr>
                        <td class="ps-4" style="max-width:260px">
                            <span style="display:block;font-weight:500;color:#0f172a;
                                  white-space:nowrap;overflow:hidden;text-overflow:ellipsis"
                                  title="{{ $f->vuln_name }}">
                                {{ $f->vuln_name }}
                            </span>
                            @if($f->vuln_category)
                            <span style="font-size:.72rem;color:#94a3b8">{{ $f->vuln_category }}</span>
                            @endif
                        </td>
                        <td><span class="sev-badge sev-{{ $f->severity }}">{{ $f->severity }}</span></td>
                        <td>
                            @if($f->cvss_score)
                            @php
                                $cvss = $f->cvss_score;
                                $cvssCol = $cvss >= 9 ? '#991b1b' : ($cvss >= 7 ? '#92400e' : ($cvss >= 4 ? '#0c4a6e' : '#166534'));
                            @endphp
                            <span style="font-weight:700;color:{{ $cvssCol }};font-size:.82rem">{{ number_format($cvss, 1) }}</span>
                            @else <span class="text-muted">—</span> @endif
                        </td>
                        <td>
                            @if($f->port && $f->port !== '0')
                                <code style="font-size:.76rem;background:#f1f5f9;padding:.1rem .35rem;border-radius:4px">
                                    {{ $f->port }}{{ $f->protocol ? '/'.$f->protocol : '' }}
                                </code>
                            @else <span class="text-muted">—</span> @endif
                        </td>
                        <td style="font-size:.76rem">
                            @if($f->cve)
                                <span style="background:#fdf4ff;color:#7e22ce;padding:.1rem .35rem;border-radius:5px;font-weight:600">{{ $f->cve }}</span>
                            @else <span class="text-muted">—</span> @endif
                        </td>
                        <td class="pe-4" style="font-size:.78rem;color:#64748b">
                            {{ $f->assessment?->name ?? '—' }}
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
