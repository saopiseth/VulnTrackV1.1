@extends('layouts.app')
@section('title', 'OS Assets — ' . $assessment->name)

@section('content')
<style>
    :root { --lime: rgb(152,194,10); --lime-dark: rgb(118,151,7); --lime-light: rgb(240,248,210); --lime-muted: rgb(232,244,195); }
    .va-card { background:#fff; border:1px solid #e8f5c2; border-radius:14px; padding:1.25rem 1.5rem; margin-bottom:1.25rem; }
    .va-card h6 { font-size:.78rem; font-weight:700; color:var(--lime-dark); text-transform:uppercase; letter-spacing:.8px; margin-bottom:.9rem; padding-bottom:.5rem; border-bottom:2px solid var(--lime); }
    .os-badge { display:inline-flex; align-items:center; gap:.3rem; padding:.18rem .55rem; border-radius:20px; font-size:.72rem; font-weight:600; }
    .conf-bar { height:5px; border-radius:20px; background:#e2e8f0; overflow:hidden; width:80px; }
    .conf-fill { height:100%; border-radius:20px; }
    .override-badge { background:#fef9c3; color:#854d0e; padding:.12rem .45rem; border-radius:10px; font-size:.68rem; font-weight:700; }
    .fam-btn { padding:.28rem .8rem; border-radius:20px; font-size:.78rem; font-weight:600; cursor:pointer; border:1.5px solid transparent; }
    .fam-btn.active { border-color:var(--lime-dark); background:var(--lime-light); color:var(--lime-dark); }
    .fam-btn:not(.active) { background:#f8fafc; color:#64748b; border-color:#e2e8f0; }
    thead.lime-head th { background:var(--lime-muted) !important; color:var(--lime-dark) !important; font-size:.75rem; }
    .icon-family { font-size:.9rem; }
</style>

{{-- Header --}}
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h4 style="margin-bottom:.2rem">OS Assets</h4>
        <div style="font-size:.84rem;color:#64748b">
            <i class="bi bi-cpu me-1"></i>{{ $assessment->name }}
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('vuln-assessments.findings', $assessment) }}" class="btn btn-sm"
            style="background:rgb(152,194,10);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.45rem 1rem">
            <i class="bi bi-table me-1"></i> Findings
        </a>
        <a href="{{ route('vuln-assessments.show', $assessment) }}" class="btn btn-sm"
            style="border:1.5px solid rgb(152,194,10);border-radius:9px;color:rgb(118,151,7);background:#fff;font-weight:500">
            <i class="bi bi-arrow-left me-1"></i> Back to Assessment
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" style="border-radius:10px;font-size:.875rem">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- OS Distribution Cards --}}
@if($osDistribution->count())
<div class="row g-2 mb-3">
    @php
        $totalHosts = $osDistribution->sum('cnt');
        $familyMeta = [
            'Windows' => ['icon' => 'bi-windows',       'bg' => '#dbeafe', 'color' => '#1e40af', 'label' => 'Windows'],
            'Linux'   => ['icon' => 'bi-ubuntu',        'bg' => '#d1fae5', 'color' => '#065f46', 'label' => 'Linux'],
            'Unix'    => ['icon' => 'bi-terminal-fill', 'bg' => '#ffedd5', 'color' => '#7c2d12', 'label' => 'Unix-based'],
            'Other'   => ['icon' => 'bi-cpu-fill',      'bg' => '#f3f4f6', 'color' => '#374151', 'label' => 'Other'],
        ];
    @endphp
    <div class="col-md-2">
        <div style="background:#fff;border:1px solid #e8f5c2;border-radius:12px;padding:.9rem 1rem;text-align:center">
            <div style="font-size:.68rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Total Hosts</div>
            <div style="font-size:1.6rem;font-weight:800;color:#0f172a">{{ $totalHosts }}</div>
        </div>
    </div>
    @foreach($osDistribution as $dist)
    @php $meta = $familyMeta[$dist->family] ?? $familyMeta['Other']; $pct = $totalHosts > 0 ? round($dist->cnt / $totalHosts * 100) : 0; @endphp
    <div class="col-md">
        <div style="background:{{ $meta['bg'] }};border:1px solid {{ $meta['bg'] }};border-radius:12px;padding:.9rem 1rem">
            <div style="font-size:.68rem;color:{{ $meta['color'] }};font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.25rem">
                <i class="bi {{ $meta['icon'] }} me-1"></i>{{ $meta['label'] }}
            </div>
            <div style="font-size:1.5rem;font-weight:800;color:{{ $meta['color'] }}">{{ $dist->cnt }}</div>
            <div style="font-size:.7rem;color:{{ $meta['color'] }};opacity:.8">{{ $pct }}% of hosts</div>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Filters --}}
<div class="va-card" style="padding:.9rem 1.25rem;margin-bottom:1rem">
    <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
        {{-- Family tabs --}}
        <div class="d-flex gap-1 flex-wrap">
            <a href="{{ route('vuln-assessments.os-assets', array_merge([$assessment->id], request()->except('family','page'))) }}"
               class="fam-btn {{ !request('family') ? 'active' : '' }}">All</a>
            @foreach(['Windows','Linux','Unix','Other'] as $fam)
            <a href="{{ route('vuln-assessments.os-assets', array_merge([$assessment->id], request()->except('family','page'), ['family'=>$fam])) }}"
               class="fam-btn {{ request('family') === $fam ? 'active' : '' }}">
                <i class="bi {{ $familyMeta[$fam]['icon'] ?? 'bi-cpu' }} me-1"></i>{{ $fam }}
            </a>
            @endforeach
        </div>
        {{-- Search --}}
        <div class="ms-auto d-flex gap-2">
            <input type="hidden" name="family" value="{{ request('family') }}">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search IP, hostname, OS…"
                value="{{ request('search') }}" style="border-radius:8px;width:220px;font-size:.82rem">
            <button type="submit" class="btn btn-sm" style="background:var(--lime);color:#fff;border-radius:8px;border:none;font-weight:600">
                <i class="bi bi-search"></i>
            </button>
            @if(request()->hasAny(['family','search']))
            <a href="{{ route('vuln-assessments.os-assets', $assessment) }}" class="btn btn-sm"
               style="border:1.5px solid #cbd5e1;border-radius:8px;color:#64748b;background:#fff">
                <i class="bi bi-x"></i>
            </a>
            @endif
        </div>
    </form>
</div>

{{-- Host OS Table --}}
<div class="va-card" style="padding:0;overflow:hidden">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.82rem">
            <thead class="lime-head">
                <tr>
                    <th style="padding:.7rem 1rem;width:130px">IP Address</th>
                    <th style="padding:.7rem .75rem">Hostname</th>
                    <th style="padding:.7rem .75rem">Detected OS</th>
                    <th style="padding:.7rem .75rem;width:90px">Family</th>
                    <th style="padding:.7rem .75rem;width:120px">Confidence</th>
                    <th style="padding:.7rem .75rem">Sources</th>
                    <th style="padding:.7rem .75rem;width:90px">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($hosts as $host)
                @php
                    $effectiveOs  = $host->os_override ?? $host->os_name;
                    $effectiveFam = $host->os_override_family ?? $host->os_family;
                    $meta = $familyMeta[$effectiveFam] ?? $familyMeta['Other'];
                    $conf = $host->os_confidence;
                    $confColor = $conf >= 80 ? '#059669' : ($conf >= 50 ? '#d97706' : '#dc2626');
                @endphp
                <tr>
                    <td style="padding:.65rem 1rem;font-family:monospace;font-weight:700;color:#0f172a;vertical-align:middle">
                        {{ $host->ip_address }}
                    </td>
                    <td style="padding:.65rem .75rem;color:#64748b;vertical-align:middle">
                        {{ $host->hostname ?? '—' }}
                    </td>
                    <td style="padding:.65rem .75rem;vertical-align:middle">
                        @if($effectiveOs)
                            <span>{{ $effectiveOs }}</span>
                            @if($host->hasOverride())
                                <span class="override-badge ms-1"><i class="bi bi-pencil-fill"></i> Override</span>
                            @endif
                            @if($host->os_history && count($host->os_history) > 0)
                                <span style="font-size:.68rem;color:#94a3b8;margin-left:.3rem" title="{{ count($host->os_history) }} prior detection(s)">
                                    <i class="bi bi-clock-history"></i> {{ count($host->os_history) }}
                                </span>
                            @endif
                        @else
                            <span style="color:#94a3b8">Not detected</span>
                        @endif
                    </td>
                    <td style="padding:.65rem .75rem;vertical-align:middle">
                        <span class="os-badge" style="background:{{ $meta['bg'] }};color:{{ $meta['color'] }}">
                            <i class="bi {{ $meta['icon'] }}"></i>{{ $effectiveFam }}
                        </span>
                    </td>
                    <td style="padding:.65rem .75rem;vertical-align:middle">
                        @if($conf > 0)
                        <div class="d-flex align-items-center gap-2">
                            <div class="conf-bar">
                                <div class="conf-fill" style="width:{{ $conf }}%;background:{{ $confColor }}"></div>
                            </div>
                            <span style="font-size:.72rem;font-weight:700;color:{{ $confColor }}">{{ $conf }}%</span>
                        </div>
                        @else
                        <span style="color:#94a3b8;font-size:.72rem">—</span>
                        @endif
                    </td>
                    <td style="padding:.65rem .75rem;vertical-align:middle">
                        @if($host->detection_sources && count($host->detection_sources))
                        <div class="d-flex flex-wrap gap-1">
                            @foreach(array_slice($host->detection_sources, 0, 3) as $src)
                            <span style="font-size:.65rem;background:#f1f5f9;color:#475569;padding:.1rem .35rem;border-radius:5px">{{ $src }}</span>
                            @endforeach
                            @if(count($host->detection_sources) > 3)
                            <span style="font-size:.65rem;color:#94a3b8">+{{ count($host->detection_sources) - 3 }}</span>
                            @endif
                        </div>
                        @else
                        <span style="color:#94a3b8;font-size:.72rem">—</span>
                        @endif
                    </td>
                    <td style="padding:.65rem .75rem;vertical-align:middle">
                        <button class="btn btn-sm"
                            style="font-size:.72rem;padding:.2rem .55rem;border-radius:7px;background:#f8fafc;border:1px solid #e2e8f0;color:#374151"
                            data-bs-toggle="modal" data-bs-target="#overrideModal{{ $host->id }}"
                            title="Set manual OS override">
                            <i class="bi bi-pencil"></i>
                        </button>
                        @if($host->os_history && count($host->os_history))
                        <button class="btn btn-sm ms-1"
                            style="font-size:.72rem;padding:.2rem .55rem;border-radius:7px;background:#f1f5f9;border:1px solid #e2e8f0;color:#64748b"
                            data-bs-toggle="modal" data-bs-target="#historyModal{{ $host->id }}"
                            title="View OS history">
                            <i class="bi bi-clock-history"></i>
                        </button>
                        @endif
                    </td>
                </tr>

                {{-- Override Modal --}}
                <div class="modal fade" id="overrideModal{{ $host->id }}" tabindex="-1">
                    <div class="modal-dialog modal-sm">
                        <div class="modal-content" style="border-radius:14px;border:1px solid #e8f5c2">
                            <div class="modal-header" style="border-bottom:2px solid rgb(152,194,10);padding:.85rem 1.25rem">
                                <h6 class="modal-title" style="font-size:.9rem;font-weight:700">
                                    <i class="bi bi-pencil me-1" style="color:rgb(152,194,10)"></i>OS Override — {{ $host->ip_address }}
                                </h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST" action="{{ route('vuln-assessments.os-override', [$assessment, $host]) }}">
                                @csrf
                                <div class="modal-body" style="padding:1.1rem 1.25rem">
                                    <div style="font-size:.75rem;color:#94a3b8;margin-bottom:.75rem">
                                        Auto-detected: <strong>{{ $host->os_name ?? 'None' }}</strong>
                                        ({{ $host->os_confidence }}% confidence)
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label" style="font-size:.78rem;font-weight:600;color:#374151">Override OS Name</label>
                                        <input type="text" name="os_override" class="form-control form-control-sm"
                                            value="{{ $host->os_override }}"
                                            placeholder="e.g. Ubuntu 22.04 LTS"
                                            style="border-radius:7px;font-size:.82rem">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label" style="font-size:.78rem;font-weight:600;color:#374151">OS Family</label>
                                        <select name="os_override_family" class="form-select form-select-sm" style="border-radius:7px;font-size:.82rem">
                                            <option value="">— Keep auto-detected —</option>
                                            @foreach(['Windows','Linux','Unix','Other'] as $fam)
                                            <option value="{{ $fam }}" {{ ($host->os_override_family ?? '') === $fam ? 'selected' : '' }}>{{ $fam }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label" style="font-size:.78rem;font-weight:600;color:#374151">Note</label>
                                        <input type="text" name="os_override_note" class="form-control form-control-sm"
                                            value="{{ $host->os_override_note }}"
                                            placeholder="Reason for override…"
                                            style="border-radius:7px;font-size:.82rem">
                                    </div>
                                    @if($host->hasOverride())
                                    <div style="font-size:.72rem;color:#94a3b8;margin-top:.5rem">
                                        Leave OS Name blank to clear the override.
                                    </div>
                                    @endif
                                </div>
                                <div class="modal-footer" style="border-top:1px solid #e8f5c2;padding:.6rem 1.25rem">
                                    <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
                                        style="border:1.5px solid #cbd5e1;border-radius:7px;color:#64748b;background:#fff">Cancel</button>
                                    <button type="submit" class="btn btn-sm"
                                        style="background:rgb(152,194,10);color:#fff;border-radius:7px;font-weight:600;border:none">
                                        Save Override
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- History Modal --}}
                @if($host->os_history && count($host->os_history))
                <div class="modal fade" id="historyModal{{ $host->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content" style="border-radius:14px;border:1px solid #e8f5c2">
                            <div class="modal-header" style="border-bottom:2px solid rgb(152,194,10);padding:.85rem 1.25rem">
                                <h6 class="modal-title" style="font-size:.9rem;font-weight:700">
                                    <i class="bi bi-clock-history me-1" style="color:rgb(152,194,10)"></i>OS History — {{ $host->ip_address }}
                                </h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body" style="padding:1.1rem 1.25rem">
                                @foreach(array_reverse($host->os_history) as $h)
                                <div style="border:1px solid #f1f5f9;border-radius:9px;padding:.65rem .9rem;margin-bottom:.5rem">
                                    <div style="font-weight:600;color:#0f172a;font-size:.84rem">{{ $h['os_name'] ?? 'Unknown' }}</div>
                                    <div style="font-size:.72rem;color:#64748b">
                                        Family: {{ $h['os_family'] ?? '—' }} &middot;
                                        Confidence: {{ $h['confidence'] ?? 0 }}% &middot;
                                        @if(!empty($h['detected_at'])) {{ $h['detected_at'] }} @endif
                                    </div>
                                </div>
                                @endforeach
                                {{-- Current --}}
                                <div style="border:1.5px solid rgb(152,194,10);border-radius:9px;padding:.65rem .9rem;background:rgb(240,248,210)">
                                    <div style="font-size:.65rem;color:rgb(118,151,7);font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.25rem">Current</div>
                                    <div style="font-weight:600;color:#0f172a;font-size:.84rem">{{ $effectiveOs ?? 'Unknown' }}</div>
                                    <div style="font-size:.72rem;color:#64748b">
                                        Family: {{ $effectiveFam }} &middot;
                                        Confidence: {{ $host->os_confidence }}%
                                        @if($host->hasOverride()) &middot; <span class="override-badge">Manual Override</span> @endif
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer" style="border-top:1px solid #e8f5c2;padding:.6rem 1.25rem">
                                <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
                                    style="border:1.5px solid #cbd5e1;border-radius:7px;color:#64748b;background:#fff">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @empty
                <tr>
                    <td colspan="7" style="padding:3rem;text-align:center;color:#94a3b8">
                        <i class="bi bi-cpu" style="font-size:2rem;display:block;margin-bottom:.75rem;opacity:.4"></i>
                        No OS asset data yet. Upload a scan to populate.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Pagination --}}
@if($hosts->hasPages())
<div class="d-flex justify-content-center mt-2">
    {{ $hosts->links() }}
</div>
@endif

@endsection
