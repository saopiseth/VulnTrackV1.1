@extends('layouts.app')
@section('title', 'Asset: '.$asset->ip_address)

@section('content')
<style>
    .detail-row { display:flex; gap:1rem; padding:.6rem 0; border-bottom:1px solid #f1f5f9; font-size:.875rem; }
    .detail-row:last-child { border-bottom:none; }
    .detail-label { width:160px; flex-shrink:0; color:#64748b; font-weight:600; font-size:.8rem; text-transform:uppercase; letter-spacing:.3px; }
    .detail-value { color:#0f172a; font-weight:500; }
    .vuln-bar { height:10px; border-radius:5px; }
</style>

<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Servers Asset Inventory</a></li>
            <li class="breadcrumb-item active">{{ $asset->ip_address }}</li>
        </ol></nav>
        <h4><i class="bi bi-hdd-fill me-2" style="color:rgb(152,194,10)"></i>{{ $asset->ip_address }}</h4>
        <p>{{ $asset->hostname ?? 'No hostname' }} &bull; {{ $asset->os ?? 'OS unknown' }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('inventory.edit', $asset) }}" class="btn btn-sm text-white" style="background:rgb(152,194,10);border-radius:10px;font-weight:600">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        <form method="POST" action="{{ route('inventory.destroy', $asset) }}" onsubmit="return confirm('Delete this asset?')">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger" style="border-radius:10px;font-weight:600">
                <i class="bi bi-trash me-1"></i> Delete
            </button>
        </form>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show border-0" style="border-radius:10px" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4">

    {{-- Left: Main Details --}}
    <div class="col-lg-8">

        {{-- Classification Banner --}}
        @php
            $clColors = [1=>'#dc2626',2=>'#d97706',3=>'#2563eb',4=>'#64748b',5=>'#9ca3af'];
            $clBg     = [1=>'#fee2e2',2=>'#fef3c7',3=>'#dbeafe',4=>'#f1f5f9',5=>'#f9fafb'];
            $color    = $clColors[$asset->classification_level] ?? '#64748b';
            $bg       = $clBg[$asset->classification_level]     ?? '#f9fafb';
        @endphp
        <div class="card mb-4" style="border:2px solid {{ $color }};background:{{ $bg }};border-radius:14px">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div style="width:52px;height:52px;border-radius:12px;background:{{ $color }};display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:900;color:#fff;flex-shrink:0">
                    {{ $asset->classification_level }}
                </div>
                <div>
                    <div style="font-size:1.1rem;font-weight:800;color:{{ $color }}">{{ $asset->critical_level }}</div>
                    <div style="font-size:.82rem;color:#64748b">Classification Level {{ $asset->classification_level }}</div>
                </div>
                <div class="ms-auto d-flex gap-2 flex-wrap">
                    @php
                        $scopeColors = ['PCI'=>['#991b1b','#fee2e2'],'DMZ'=>['#92400e','#fef3c7'],'Internal'=>['#1e40af','#dbeafe'],'External'=>['#065f46','#d1fae5'],'Third-Party'=>['#374151','#f3f4f6']];
                        [$sc,$sb] = $scopeColors[$asset->identified_scope] ?? ['#374151','#f3f4f6'];
                    @endphp
                    <span style="background:{{ $sb }};color:{{ $sc }};font-size:.8rem;font-weight:700;padding:.3rem .7rem;border-radius:8px;letter-spacing:.5px">
                        {{ $asset->identified_scope }}
                    </span>
                    @php
                        $envColors = ['PROD'=>['#dc2626','#fee2e2'],'UAT'=>['#d97706','#fef3c7'],'STAGE'=>['#2563eb','#dbeafe']];
                        [$ec,$eb] = $envColors[$asset->environment] ?? ['#374151','#f3f4f6'];
                    @endphp
                    <span style="background:{{ $eb }};color:{{ $ec }};font-size:.8rem;font-weight:700;padding:.3rem .7rem;border-radius:8px;letter-spacing:.5px">
                        {{ $asset->environment }}
                    </span>
                    @php
                        $stColors = ['Active'=>['#16a34a','#dcfce7'],'Inactive'=>['#d97706','#fef3c7'],'Decommissioned'=>['#64748b','#f1f5f9']];
                        [$stc,$stb] = $stColors[$asset->status] ?? ['#64748b','#f1f5f9'];
                    @endphp
                    <span style="background:{{ $stb }};color:{{ $stc }};font-size:.8rem;font-weight:700;padding:.3rem .7rem;border-radius:8px;letter-spacing:.5px">
                        {{ $asset->status }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Asset Info --}}
        <div class="card mb-4">
            <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #e2e8f0">
                <h6 class="mb-0 fw-700" style="font-size:.95rem"><i class="bi bi-info-circle-fill me-2" style="color:rgb(152,194,10)"></i>Asset Information</h6>
            </div>
            <div class="card-body">
                <div class="detail-row"><span class="detail-label">IP Address</span><span class="detail-value" style="font-family:monospace;font-weight:700">{{ $asset->ip_address }}</span></div>
                <div class="detail-row"><span class="detail-label">Hostname</span><span class="detail-value">{{ $asset->hostname ?? '—' }}</span></div>
                <div class="detail-row"><span class="detail-label">Operating System</span><span class="detail-value">{{ $asset->os ?? '—' }}</span></div>
                <div class="detail-row"><span class="detail-label">Open Ports</span><span class="detail-value" style="font-family:monospace">{{ $asset->open_ports ?? '—' }}</span></div>
                <div class="detail-row"><span class="detail-label">System Name</span><span class="detail-value">{{ $asset->system_name ?? '—' }}</span></div>
                <div class="detail-row"><span class="detail-label">Tags</span><span class="detail-value">
                    @if($asset->tags)
                        @foreach(explode(',', $asset->tags) as $tag)
                            <span style="background:#f1f5f9;color:#475569;font-size:.75rem;padding:.2rem .5rem;border-radius:6px;margin-right:4px">{{ trim($tag) }}</span>
                        @endforeach
                    @else —
                    @endif
                </span></div>
                <div class="detail-row"><span class="detail-label">Last Scanned</span><span class="detail-value">{{ $asset->last_scanned_at ? $asset->last_scanned_at->format('d M Y H:i') : '—' }}</span></div>
                <div class="detail-row"><span class="detail-label">Added By</span><span class="detail-value">{{ $asset->creator?->name ?? '—' }}</span></div>
                <div class="detail-row"><span class="detail-label">Created</span><span class="detail-value">{{ $asset->created_at->format('d M Y H:i') }}</span></div>
            </div>
        </div>

        @if($asset->notes)
        <div class="card mb-4">
            <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #e2e8f0">
                <h6 class="mb-0 fw-700" style="font-size:.95rem"><i class="bi bi-sticky-fill me-2 text-warning"></i>Notes</h6>
            </div>
            <div class="card-body">
                <p class="mb-0" style="white-space:pre-wrap;font-size:.875rem;color:#374151">{{ $asset->notes }}</p>
            </div>
        </div>
        @endif
    </div>

    {{-- Right: Vulnerability Summary --}}
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #e2e8f0">
                <h6 class="mb-0 fw-700" style="font-size:.95rem"><i class="bi bi-shield-exclamation me-2 text-danger"></i>Vulnerability Summary</h6>
            </div>
            <div class="card-body">
                @php $total = $asset->totalVulns(); @endphp
                <div class="text-center mb-4">
                    <div style="font-size:2.5rem;font-weight:900;color:#0f172a;line-height:1">{{ $total }}</div>
                    <div style="font-size:.8rem;color:#64748b;font-weight:600">Total Vulnerabilities</div>
                </div>

                @foreach([
                    ['Critical', $asset->vuln_critical, '#dc2626', '#fee2e2'],
                    ['High',     $asset->vuln_high,     '#d97706', '#fef3c7'],
                    ['Medium',   $asset->vuln_medium,   '#2563eb', '#dbeafe'],
                    ['Low',      $asset->vuln_low,      '#374151', '#f3f4f6'],
                ] as [$label, $count, $color, $bg])
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:.82rem;font-weight:600;color:{{ $color }}">{{ $label }}</span>
                        <span style="font-size:.82rem;font-weight:800;color:{{ $color }}">{{ $count }}</span>
                    </div>
                    <div style="background:#f1f5f9;border-radius:5px;height:8px;overflow:hidden">
                        <div class="vuln-bar" style="width:{{ $total > 0 ? round(($count/$total)*100) : 0 }}%;background:{{ $color }}"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Matching Scan Data (Summary Row) --}}
        <div class="card">
            <div class="card-header bg-white" style="padding:1rem 1.25rem;border-bottom:1px solid #e2e8f0">
                <h6 class="mb-0 fw-700" style="font-size:.95rem"><i class="bi bi-table me-2" style="color:rgb(152,194,10)"></i>Inventory Record</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:.78rem">
                    <tr><td class="ps-3 text-muted">IP</td><td class="fw-600" style="font-family:monospace">{{ $asset->ip_address }}</td></tr>
                    <tr><td class="ps-3 text-muted">Hostname</td><td>{{ $asset->hostname ?? '—' }}</td></tr>
                    <tr><td class="ps-3 text-muted">Scope</td><td class="fw-700">{{ $asset->identified_scope }}</td></tr>
                    <tr><td class="ps-3 text-muted">Env</td><td class="fw-700">{{ $asset->environment }}</td></tr>
                    <tr><td class="ps-3 text-muted">System</td><td>{{ $asset->system_name ?? '—' }}</td></tr>
                    <tr><td class="ps-3 text-muted">Class #</td><td class="fw-700">{{ $asset->classification_level }}</td></tr>
                    <tr><td class="ps-3 text-muted">Level</td><td class="fw-700">{{ $asset->critical_level }}</td></tr>
                    <tr><td class="ps-3 text-muted">Scan Data</td><td>
                        {{ $asset->os ?? 'OS unknown' }},
                        Ports: {{ $asset->open_ports ?? 'N/A' }},
                        {{ $asset->vuln_critical }}C / {{ $asset->vuln_high }}H / {{ $asset->vuln_medium }}M / {{ $asset->vuln_low }}L
                    </td></tr>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
