@extends('layouts.app')

@section('title', $test->name)

@section('content')
<div class="page-header d-flex align-items-start justify-content-between gap-2">
    <div class="min-w-0">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('segmentation.index') }}">Segmentation Tests</a></li>
                <li class="breadcrumb-item active">{{ Str::limit($test->name, 60) }}</li>
            </ol>
        </nav>
        <h4><i class="bi bi-diagram-3-fill me-2" style="color:var(--primary)"></i>{{ $test->name }}</h4>
        <p>
            Scanner IP: <strong>{{ $test->scanner_ip ?? '—' }}</strong>
            &bull; Scanner Subnet: <strong>{{ $test->scanner_subnet ?? '—' }}</strong>
            &bull; Uploaded {{ $test->created_at->format('d M Y H:i') }}
            @if($test->creator) &bull; by {{ $test->creator->name }} @endif
        </p>
    </div>
    <div class="d-flex gap-2 flex-shrink-0">
        @if($test->upload_status === 'completed')
        <a href="{{ route('segmentation.export-csv', $test) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-filetype-csv me-1"></i>Export CSV
        </a>
        @endif
        <form method="POST" action="{{ route('segmentation.destroy', $test) }}"
              onsubmit="return confirm('Delete this segmentation test and all results?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-trash3 me-1"></i>Delete
            </button>
        </form>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Processing status --}}
@if(in_array($test->upload_status, ['pending','processing']))
<div class="alert alert-info d-flex align-items-center gap-2 mb-3" id="processingAlert">
    <div class="spinner-border spinner-border-sm text-info flex-shrink-0"></div>
    <div>
        <strong>Analysing Nmap scan…</strong>
        This page will refresh automatically when processing is complete.
    </div>
</div>
<script nonce="{{ csp_nonce() }}">
(function(){
    var poll = setInterval(function(){
        fetch('{{ route("segmentation.status", $test) }}')
            .then(function(r){ return r.json(); })
            .then(function(d){
                if(d.status === 'completed' || d.status === 'failed'){
                    clearInterval(poll);
                    location.reload();
                }
            }).catch(function(){});
    }, 3000);
})();
</script>
@endif

@if($test->upload_status === 'failed')
<div class="alert alert-danger mb-3">
    <strong>Processing failed:</strong> {{ $test->upload_error ?? 'An unknown error occurred.' }}
    <div class="mt-1" style="font-size:.82rem">Delete this test and re-upload the scan file.</div>
</div>
@endif

@if($test->notes)
<div class="alert mb-3" style="background:#f8fafc;border:1px solid var(--border);border-radius:12px">
    <i class="bi bi-sticky-fill me-2" style="color:var(--primary)"></i>
    {{ $test->notes }}
</div>
@endif

<style>
.section-card {
    background:#fff; border:1px solid var(--border); border-radius:14px;
    box-shadow:0 1px 4px rgba(0,0,0,.04); margin-bottom:1.5rem; overflow:hidden;
}
.section-card-header {
    padding:1rem 1.35rem; border-bottom:1px solid var(--border);
    display:flex; align-items:center; justify-content:space-between; gap:1rem;
}
.section-card-header h6 {
    font-size:.9rem; font-weight:700; text-transform:uppercase;
    letter-spacing:.5px; color:var(--primary-dark); margin:0;
}
.section-card-body { padding:0; }

/* Status badges */
.badge-accessible {
    background:#dcfce7; color:#16a34a;
    font-size:.75rem; font-weight:700; padding:.3rem .75rem;
    border-radius:20px; display:inline-block; white-space:nowrap;
}
.badge-not-accessible {
    background:#fee2e2; color:#dc2626;
    font-size:.75rem; font-weight:700; padding:.3rem .75rem;
    border-radius:20px; display:inline-block; white-space:nowrap;
}

/* Report tables */
.report-table { width:100%; border-collapse:collapse; font-size:.85rem; }
.report-table thead th {
    background:var(--body-bg); color:#374151; font-weight:700;
    font-size:.75rem; text-transform:uppercase; letter-spacing:.5px;
    padding:.85rem 1.25rem; border-bottom:1px solid var(--border);
    white-space:nowrap;
}
.report-table tbody td {
    padding:.8rem 1.25rem; border-bottom:1px solid #f1f5f9;
    vertical-align:middle; color:#374151;
}
.report-table tbody tr:last-child td { border-bottom:none; }
.report-table tbody tr:hover td { background:#f8fafc; }

.ip-mono  { font-family:'Courier New',monospace; font-size:.83rem; color:#0f172a; }
.port-tag {
    display:inline-block; background:#f1f5f9; color:#374151;
    border-radius:6px; padding:.1rem .45rem; font-size:.75rem;
    font-weight:600; margin:.1rem .1rem .1rem 0; font-family:monospace;
}
.svc-tag {
    display:inline-block; background:color-mix(in srgb,var(--primary) 14%,white);
    color:var(--primary-dark); border-radius:6px;
    padding:.1rem .45rem; font-size:.75rem; font-weight:600; margin:.1rem .1rem .1rem 0;
}

/* Filter bar */
.filter-bar {
    display:flex; gap:.6rem; flex-wrap:wrap; align-items:center;
    padding:.85rem 1.25rem; border-bottom:1px solid var(--border);
    background:var(--body-bg);
}
.filter-bar input, .filter-bar select {
    font-size:.8rem; border:1.5px solid var(--border); border-radius:8px;
    padding:.35rem .7rem; outline:none; background:#fff;
}
.filter-bar input:focus, .filter-bar select:focus { border-color:var(--primary); }
.filter-bar label { font-size:.78rem; font-weight:600; color:#64748b; }

/* Summary stats --*/
.stat-pill {
    display:inline-flex; align-items:center; gap:.4rem;
    padding:.35rem .9rem; border-radius:20px; font-size:.78rem; font-weight:700;
}
.stat-pill.acc  { background:#dcfce7; color:#16a34a; }
.stat-pill.nacc { background:#fee2e2; color:#dc2626; }
.stat-pill.total{ background:#f1f5f9; color:#374151; }
</style>

@if($test->upload_status === 'completed')

{{-- ── Stat pills ── --}}
@php
    $totalSubnets   = $results->count();
    $accessibleCnt  = $results->where('status','accessible')->count();
    $notAccessCnt   = $results->where('status','not_accessible')->count();
    $totalHosts     = $results->sum('host_count');
    $totalPorts     = $details->whereNotNull('port')->count();
@endphp
<div class="d-flex flex-wrap gap-2 mb-4">
    <span class="stat-pill total"><i class="bi bi-grid-1x2"></i>{{ $totalSubnets }} Subnet(s)</span>
    <span class="stat-pill acc"><i class="bi bi-check-circle-fill"></i>{{ $accessibleCnt }} Accessible</span>
    <span class="stat-pill nacc"><i class="bi bi-x-circle-fill"></i>{{ $notAccessCnt }} Blocked</span>
    <span class="stat-pill total"><i class="bi bi-pc-display-horizontal"></i>{{ $totalHosts }} Host(s)</span>
    <span class="stat-pill total"><i class="bi bi-plug-fill"></i>{{ $totalPorts }} Open Port(s)</span>
</div>

{{-- ── 1. Summary Report ── --}}
<div class="section-card">
    <div class="section-card-header">
        <h6><i class="bi bi-table me-2"></i>Summary Report</h6>
        <span class="text-muted" style="font-size:.78rem">Segmentation status per target subnet</span>
    </div>
    <div class="section-card-body">
        @if($results->isEmpty())
        <div class="p-4 text-center text-muted" style="font-size:.875rem">
            No subnets found in this scan. Check that the Nmap file contains live hosts.
        </div>
        @else
        <table class="report-table">
            <thead>
                <tr>
                    <th>Scanner Subnet</th>
                    <th>Target Subnet</th>
                    <th>Hosts Found</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($results as $result)
                <tr>
                    <td class="ip-mono">{{ $test->scanner_subnet ?? '—' }}</td>
                    <td class="ip-mono">{{ $result->target_subnet }}</td>
                    <td>{{ $result->host_count }}</td>
                    <td>
                        @if($result->status === 'accessible')
                            <span class="badge-accessible"><i class="bi bi-check-circle-fill me-1"></i>Accessible</span>
                        @else
                            <span class="badge-not-accessible"><i class="bi bi-x-circle-fill me-1"></i>Not Accessible</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

{{-- ── 2. Detailed Report ── --}}
<div class="section-card">
    <div class="section-card-header">
        <h6><i class="bi bi-list-ul me-2"></i>Detailed Report</h6>
        <span class="text-muted" style="font-size:.78rem">Per-host open ports and services</span>
    </div>

    {{-- Filters --}}
    <div class="filter-bar">
        <label>Filter:</label>
        <input type="text" id="filterSubnet" placeholder="Target subnet…" style="width:155px">
        <input type="text" id="filterHost"   placeholder="Host IP…"       style="width:140px">
        <input type="text" id="filterPort"   placeholder="Port…"          style="width:90px">
        <input type="text" id="filterSvc"    placeholder="Service…"       style="width:120px">
        <button class="btn btn-sm btn-outline-secondary" id="clearFilters" style="font-size:.78rem">
            <i class="bi bi-x-lg me-1"></i>Clear
        </button>
        <span class="ms-auto text-muted" style="font-size:.78rem" id="rowCount"></span>
    </div>

    <div class="section-card-body" style="overflow-x:auto">
        @if($details->isEmpty())
        <div class="p-4 text-center text-muted" style="font-size:.875rem">
            No host details found. The scan may not have discovered any live hosts with open ports.
        </div>
        @else
        <table class="report-table" id="detailTable">
            <thead>
                <tr>
                    <th>Scanner IP</th>
                    <th>Scanner Subnet</th>
                    <th>Target Subnet</th>
                    <th>Host IP</th>
                    <th>Port</th>
                    <th>Protocol</th>
                    <th>Service</th>
                </tr>
            </thead>
            <tbody id="detailBody">
                @foreach($details as $detail)
                <tr
                    data-subnet="{{ $detail->target_subnet }}"
                    data-host="{{ $detail->host_ip }}"
                    data-port="{{ $detail->port ?? '' }}"
                    data-svc="{{ strtolower($detail->service ?? '') }}"
                >
                    <td class="ip-mono">{{ $test->scanner_ip ?? '—' }}</td>
                    <td class="ip-mono">{{ $test->scanner_subnet ?? '—' }}</td>
                    <td class="ip-mono">{{ $detail->target_subnet }}</td>
                    <td class="ip-mono">{{ $detail->host_ip }}</td>
                    <td>
                        @if($detail->port)
                            <span class="port-tag">{{ $detail->port }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($detail->protocol)
                            <span style="font-size:.78rem;color:#64748b;text-transform:uppercase;font-weight:600">{{ $detail->protocol }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($detail->service)
                            <span class="svc-tag">{{ $detail->service }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

@endif {{-- upload_status === 'completed' --}}

@push('scripts')
<script nonce="{{ csp_nonce() }}">
(function(){
    var rows       = Array.from(document.querySelectorAll('#detailBody tr') || []);
    var fSubnet    = document.getElementById('filterSubnet');
    var fHost      = document.getElementById('filterHost');
    var fPort      = document.getElementById('filterPort');
    var fSvc       = document.getElementById('filterSvc');
    var clearBtn   = document.getElementById('clearFilters');
    var rowCount   = document.getElementById('rowCount');

    function applyFilters() {
        var subnet = (fSubnet ? fSubnet.value.toLowerCase().trim() : '');
        var host   = (fHost   ? fHost.value.toLowerCase().trim()   : '');
        var port   = (fPort   ? fPort.value.trim()                 : '');
        var svc    = (fSvc    ? fSvc.value.toLowerCase().trim()    : '');

        var visible = 0;
        rows.forEach(function(row) {
            var show =
                (!subnet || row.dataset.subnet.includes(subnet)) &&
                (!host   || row.dataset.host.includes(host))     &&
                (!port   || row.dataset.port === port)           &&
                (!svc    || row.dataset.svc.includes(svc));
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (rowCount) rowCount.textContent = visible + ' of ' + rows.length + ' rows';
    }

    function updateCount() { applyFilters(); }

    if (fSubnet) fSubnet.addEventListener('input', applyFilters);
    if (fHost)   fHost.addEventListener('input', applyFilters);
    if (fPort)   fPort.addEventListener('input', applyFilters);
    if (fSvc)    fSvc.addEventListener('input', applyFilters);

    if (clearBtn) {
        clearBtn.addEventListener('click', function(){
            if (fSubnet) fSubnet.value = '';
            if (fHost)   fHost.value   = '';
            if (fPort)   fPort.value   = '';
            if (fSvc)    fSvc.value    = '';
            applyFilters();
        });
    }

    // Initial count
    applyFilters();
})();
</script>
@endpush
@endsection
