@extends('layouts.app')
@section('title', 'Installed Applications — ' . $hostOs->ip_address)

@section('content')
<style>
    :root { --lime: rgb(152,194,10); --lime-dark: rgb(118,151,7); --lime-light: rgb(240,248,210); --lime-muted: rgb(232,244,195); }
    .va-card { background:#fff; border:1px solid #e8f5c2; border-radius:14px; padding:1.25rem 1.5rem; margin-bottom:1.25rem; }
    .va-card h6 { font-size:.78rem; font-weight:700; color:var(--lime-dark); text-transform:uppercase; letter-spacing:.8px; margin-bottom:.9rem; padding-bottom:.5rem; border-bottom:2px solid var(--lime); }
    thead.lime-head th { background:var(--lime-muted) !important; color:var(--lime-dark) !important; font-size:.75rem; }
    .cat-btn { padding:.28rem .8rem; border-radius:20px; font-size:.78rem; font-weight:600; cursor:pointer; border:1.5px solid transparent; text-decoration:none; display:inline-block; }
    .cat-btn.active { border-color:var(--lime-dark); background:var(--lime-light); color:var(--lime-dark); }
    .cat-btn:not(.active) { background:#f8fafc; color:#64748b; border-color:#e2e8f0; }
    .cat-badge { display:inline-block; padding:.15rem .55rem; border-radius:20px; font-size:.7rem; font-weight:600; }
    .xcpe-badge { background:#fef9c3; color:#854d0e; padding:.1rem .4rem; border-radius:8px; font-size:.65rem; font-weight:700; margin-left:.3rem; }
    .os-badge { display:inline-flex; align-items:center; gap:.3rem; padding:.18rem .55rem; border-radius:20px; font-size:.72rem; font-weight:600; }
</style>

@php
    $familyMeta = [
        'Windows' => ['icon' => 'bi-windows',       'bg' => '#dbeafe', 'color' => '#1e40af'],
        'Linux'   => ['icon' => 'bi-ubuntu',        'bg' => '#d1fae5', 'color' => '#065f46'],
        'Unix'    => ['icon' => 'bi-terminal-fill', 'bg' => '#ffedd5', 'color' => '#7c2d12'],
        'Other'   => ['icon' => 'bi-cpu-fill',      'bg' => '#f3f4f6', 'color' => '#374151'],
    ];

    $effectiveOs  = $hostOs->os_override ?? $hostOs->os_name;
    $effectiveFam = $hostOs->os_override_family ?? $hostOs->os_family;
    $osMeta       = $familyMeta[$effectiveFam] ?? $familyMeta['Other'];

    // Category colour map
    $catColors = [
        'Browser'        => ['bg' => '#dbeafe', 'color' => '#1e40af'],
        'Runtime'        => ['bg' => '#d1fae5', 'color' => '#065f46'],
        'Security'       => ['bg' => '#fee2e2', 'color' => '#991b1b'],
        'Microsoft'      => ['bg' => '#ede9fe', 'color' => '#5b21b6'],
        'Developer Tools'=> ['bg' => '#fef3c7', 'color' => '#92400e'],
        'Web Server'     => ['bg' => '#ffedd5', 'color' => '#7c2d12'],
        'Database'       => ['bg' => '#fce7f3', 'color' => '#9d174d'],
        'Library / SDK'  => ['bg' => '#f0fdf4', 'color' => '#166534'],
        'Network / VPN'  => ['bg' => '#e0f2fe', 'color' => '#075985'],
        'Remote Access'  => ['bg' => '#f5f3ff', 'color' => '#4c1d95'],
        'Other'          => ['bg' => '#f3f4f6', 'color' => '#374151'],
    ];

    $allCategories = $apps->pluck('category')->unique()->sort()->values();
    $activeCategory = request('category');
    $filteredApps   = $activeCategory
        ? $apps->filter(fn($a) => $a['category'] === $activeCategory)->values()
        : $apps;
@endphp

{{-- Header --}}
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h4 style="margin-bottom:.2rem">
            <i class="bi bi-grid-3x3-gap me-2" style="color:var(--lime-dark)"></i>Installed Applications
        </h4>
        <div style="font-size:.84rem;color:#64748b">
            <span class="os-badge me-2" style="background:{{ $osMeta['bg'] }};color:{{ $osMeta['color'] }}">
                <i class="bi {{ $osMeta['icon'] }}"></i>{{ $effectiveFam }}
            </span>
            <span style="font-family:monospace;font-weight:700;color:#0f172a">{{ $hostOs->ip_address }}</span>
            @if($hostOs->hostname)
                <span style="color:#94a3b8;margin:0 .35rem">·</span>{{ $hostOs->hostname }}
            @endif
            @if($effectiveOs)
                <span style="color:#94a3b8;margin:0 .35rem">·</span>{{ $effectiveOs }}
            @endif
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('inventory.os-assets') }}" class="btn btn-sm"
            style="border:1.5px solid rgb(152,194,10);border-radius:9px;color:rgb(118,151,7);background:#fff;font-weight:500">
            <i class="bi bi-arrow-left me-1"></i> Back to OS Assets
        </a>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-2 mb-3">
    <div class="col-md-2 d-flex">
        <div style="background:#fff;border:1px solid #e8f5c2;border-radius:12px;padding:.9rem 1rem;
                    text-align:center;width:100%;display:flex;flex-direction:column;justify-content:center">
            <div style="font-size:.68rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Total Apps</div>
            <div style="font-size:1.6rem;font-weight:800;color:#0f172a;line-height:1.1">{{ $apps->count() }}</div>
            <div style="font-size:.7rem;color:#cbd5e1;margin-top:.15rem">detected via CPE</div>
        </div>
    </div>
    @foreach($allCategories as $cat)
    @php
        $cnt = $apps->where('category', $cat)->count();
        $cm  = $catColors[$cat] ?? $catColors['Other'];
    @endphp
    <div class="col-md d-flex">
        <div style="background:{{ $cm['bg'] }};border:1px solid {{ $cm['bg'] }};border-radius:12px;
                    padding:.9rem 1rem;width:100%;min-width:0">
            <div style="font-size:.65rem;color:{{ $cm['color'] }};font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.25rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                {{ $cat }}
            </div>
            <div style="font-size:1.4rem;font-weight:800;color:{{ $cm['color'] }}">{{ $cnt }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- Category Filter --}}
@if($allCategories->count() > 1)
<div class="va-card" style="padding:.9rem 1.25rem;margin-bottom:1rem">
    <div class="d-flex gap-1 flex-wrap align-items-center">
        <a href="{{ route('inventory.os-assets.apps', $hostOs) }}"
           class="cat-btn {{ !$activeCategory ? 'active' : '' }}">All</a>
        @foreach($allCategories as $cat)
        <a href="{{ route('inventory.os-assets.apps', ['hostOs' => $hostOs->id, 'category' => $cat]) }}"
           class="cat-btn {{ $activeCategory === $cat ? 'active' : '' }}">
            {{ $cat }}
            <span style="font-size:.7rem;opacity:.7;margin-left:.2rem">{{ $apps->where('category', $cat)->count() }}</span>
        </a>
        @endforeach
    </div>
</div>
@endif

{{-- Applications Table --}}
<div class="va-card" style="padding:0;overflow:hidden">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.82rem">
            <thead class="lime-head">
                <tr>
                    <th style="padding:.7rem 1rem">#</th>
                    <th style="padding:.7rem .75rem">Application Name</th>
                    <th style="padding:.7rem .75rem">Vendor</th>
                    <th style="padding:.7rem .75rem;width:130px">Version</th>
                    <th style="padding:.7rem .75rem;width:150px">Category</th>
                </tr>
            </thead>
            <tbody>
                @forelse($filteredApps as $i => $app)
                @php $cm = $catColors[$app['category']] ?? $catColors['Other']; @endphp
                <tr>
                    <td style="padding:.6rem 1rem;color:#94a3b8;font-size:.75rem;vertical-align:middle">{{ $i + 1 }}</td>
                    <td style="padding:.6rem .75rem;vertical-align:middle">
                        <span style="font-weight:600;color:#0f172a">{{ $app['name'] }}</span>
                        @if($app['is_xcpe'])
                            <span class="xcpe-badge" title="Extended CPE (community-contributed)">x-cpe</span>
                        @endif
                    </td>
                    <td style="padding:.6rem .75rem;color:#64748b;vertical-align:middle">{{ $app['vendor'] }}</td>
                    <td style="padding:.6rem .75rem;vertical-align:middle">
                        <span style="font-family:monospace;font-size:.78rem;color:#374151">{{ $app['version'] }}</span>
                    </td>
                    <td style="padding:.6rem .75rem;vertical-align:middle">
                        <span class="cat-badge" style="background:{{ $cm['bg'] }};color:{{ $cm['color'] }}">
                            {{ $app['category'] }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="padding:3rem;text-align:center;color:#94a3b8">
                        <i class="bi bi-grid-3x3-gap" style="font-size:2rem;display:block;margin-bottom:.75rem;opacity:.4"></i>
                        @if($activeCategory)
                            No applications in the <strong>{{ $activeCategory }}</strong> category.
                        @else
                            No application data found for this host.<br>
                            <span style="font-size:.78rem">Applications are detected via CPE enumeration (plugin 45590) during a Nessus scan.</span>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($apps->count())
<div style="font-size:.75rem;color:#94a3b8;text-align:right;margin-top:-.5rem">
    <i class="bi bi-info-circle me-1"></i>
    Applications detected via CPE enumeration (Nessus plugin 45590).
    <span class="xcpe-badge" style="vertical-align:middle">x-cpe</span> entries are community-extended CPE identifiers.
</div>
@endif

@endsection
