@extends('layouts.app')
@section('title', 'Vulnerability Management')

@section('content')

<style>
    :root { --lime: var(--primary); --lime-dark: var(--primary-dark); --lime-light: rgb(240,248,210); --lime-muted: rgb(232,244,195); }
    .vm-card { background:#fff; border:1px solid #e8f5c2; border-radius:14px; padding:1.5rem; margin-bottom:1.25rem; }
    .vm-card h6 { font-size:.8rem; font-weight:700; color:var(--lime-dark); text-transform:uppercase; letter-spacing:.8px; margin-bottom:1rem; padding-bottom:.5rem; border-bottom:2px solid var(--lime); }
    .stat-box { border-radius:12px; padding:1rem 1.2rem; }
    .badge-sev { padding:.28rem .7rem; border-radius:20px; font-size:.72rem; font-weight:700; display:inline-block; white-space:nowrap; }
    .sev-critical { background:#fee2e2; color:#991b1b; }
    .sev-high     { background:#ffedd5; color:#9a3412; }
    .sev-medium   { background:#fef9c3; color:#854d0e; }
    .sev-low      { background:#f1f5f9; color:#475569; }
    .status-open        { background:#fee2e2; color:#991b1b; }
    .status-in-progress { background:#fef9c3; color:#854d0e; }
    .status-resolved    { background:#d1fae5; color:#065f46; }
    thead.lime-head th { background:var(--lime-muted) !important; color:var(--lime-dark) !important; }
</style>

{{-- Header --}}
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h4><i class="bi bi-bug-fill me-2" style="color:var(--lime)"></i>Vulnerability Management</h4>
        <p>Upload scan results and track remediation progress.</p>
    </div>
    <button class="btn btn-sm" style="background:var(--primary);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.45rem 1rem"
        data-bs-toggle="modal" data-bs-target="#uploadModal">
        <i class="bi bi-upload me-1"></i> Upload Scan
    </button>
</div>

{{-- Flash --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius:10px;font-size:.875rem">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Summary Stats --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3 col-xl">
        <div class="stat-box" style="background:#fff;border:1px solid #e8f5c2">
            <div style="font-size:.72rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Total</div>
            <div style="font-size:1.6rem;font-weight:800;color:#0f172a">{{ $stats->total ?? 0 }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl">
        <div class="stat-box" style="background:#fee2e2;border:1px solid #fca5a5">
            <div style="font-size:.72rem;color:#991b1b;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Critical</div>
            <div style="font-size:1.6rem;font-weight:800;color:#991b1b">{{ $stats->critical ?? 0 }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl">
        <div class="stat-box" style="background:#ffedd5;border:1px solid #fdba74">
            <div style="font-size:.72rem;color:#9a3412;font-weight:600;text-transform:uppercase;letter-spacing:.5px">High</div>
            <div style="font-size:1.6rem;font-weight:800;color:#9a3412">{{ $stats->high ?? 0 }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl">
        <div class="stat-box" style="background:#fef9c3;border:1px solid #fde047">
            <div style="font-size:.72rem;color:#854d0e;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Medium</div>
            <div style="font-size:1.6rem;font-weight:800;color:#854d0e">{{ $stats->medium ?? 0 }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl">
        <div class="stat-box" style="background:#f1f5f9;border:1px solid #cbd5e1">
            <div style="font-size:.72rem;color:#475569;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Low</div>
            <div style="font-size:1.6rem;font-weight:800;color:#475569">{{ $stats->low ?? 0 }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl">
        <div class="stat-box" style="background:#d1fae5;border:1px solid #6ee7b7">
            <div style="font-size:.72rem;color:#065f46;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Resolved</div>
            <div style="font-size:1.6rem;font-weight:800;color:#065f46">{{ $stats->resolved ?? 0 }}</div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="vm-card" style="padding:1rem 1.5rem;margin-bottom:1.25rem">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-12 col-md-5">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search title, asset, ID…" value="{{ request('search') }}" style="border-radius:8px">
        </div>
        <div class="col-6 col-md-2">
            <select name="severity" class="form-select form-select-sm" style="border-radius:8px">
                <option value="">All Severities</option>
                @foreach(\App\Models\Vulnerability::severities() as $s)
                <option value="{{ $s }}" @selected(request('severity')===$s)>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select name="status" class="form-select form-select-sm" style="border-radius:8px">
                <option value="">All Statuses</option>
                @foreach(\App\Models\Vulnerability::statuses() as $s)
                <option value="{{ $s }}" @selected(request('status')===$s)>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-sm" style="background:var(--primary);color:#fff;border-radius:8px;border:none;font-weight:600">
                <i class="bi bi-funnel me-1"></i>Filter
            </button>
            @if(request()->hasAny(['search','severity','status']))
            <a href="{{ route('vulnerabilities.index') }}" class="btn btn-sm ms-1" style="border:1.5px solid #cbd5e1;border-radius:8px;color:#64748b;background:#fff;font-weight:500">Clear</a>
            @endif
        </div>
    </form>
</div>

{{-- Table --}}
<div class="vm-card" style="padding:0;overflow:hidden">
    <div class="table-responsive">
        <table class="table" style="margin:0;font-size:.83rem">
            <thead class="lime-head">
                <tr>
                    <th style="padding:.6rem 1rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-color:#e2e8f0">ID</th>
                    <th style="padding:.6rem 1rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-color:#e2e8f0">Severity</th>
                    <th style="padding:.6rem 1rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-color:#e2e8f0">Title</th>
                    <th style="padding:.6rem 1rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-color:#e2e8f0">Asset / System</th>
                    <th style="padding:.6rem 1rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-color:#e2e8f0">Status</th>
                    <th style="padding:.6rem 1rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-color:#e2e8f0">Source</th>
                    <th style="padding:.6rem 1rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-color:#e2e8f0;text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vulnerabilities as $v)
                @php
                    $sevClass = 'sev-' . strtolower($v->severity);
                    $stClass  = 'status-' . str_replace(' ', '-', strtolower($v->status));
                @endphp
                <tr style="border-color:#f1f5f9">
                    <td style="padding:.6rem 1rem;vertical-align:middle;border-color:#f1f5f9;color:#94a3b8;font-family:monospace;font-size:.78rem">
                        {{ $v->vuln_id ?? '#'.$v->id }}
                    </td>
                    <td style="padding:.6rem 1rem;vertical-align:middle;border-color:#f1f5f9">
                        <span class="badge-sev {{ $sevClass }}">{{ $v->severity }}</span>
                    </td>
                    <td style="padding:.6rem 1rem;vertical-align:middle;border-color:#f1f5f9;max-width:280px">
                        <div style="font-weight:600;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $v->title }}">{{ $v->title }}</div>
                        @if($v->description)
                        <div style="font-size:.75rem;color:#94a3b8;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $v->description }}">{{ Str::limit($v->description, 80) }}</div>
                        @endif
                    </td>
                    <td style="padding:.6rem 1rem;vertical-align:middle;border-color:#f1f5f9;color:#374151;font-weight:500">{{ $v->asset }}</td>
                    <td style="padding:.6rem 1rem;vertical-align:middle;border-color:#f1f5f9">
                        <form method="POST" action="{{ route('vulnerabilities.status', $v) }}">
                            @csrf @method('PATCH')
                            <select name="status" onchange="this.form.submit()"
                                class="form-select form-select-sm badge-sev {{ $stClass }}"
                                style="border:none;border-radius:20px;font-size:.72rem;font-weight:700;padding:.2rem .6rem;cursor:pointer;width:auto">
                                @foreach(\App\Models\Vulnerability::statuses() as $s)
                                <option value="{{ $s }}" @selected($v->status===$s)>{{ $s }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                    <td style="padding:.6rem 1rem;vertical-align:middle;border-color:#f1f5f9;color:#94a3b8;font-size:.75rem">
                        {{ $v->source_file ?? '—' }}
                    </td>
                    <td style="padding:.6rem 1rem;vertical-align:middle;border-color:#f1f5f9;text-align:right">
                        <button class="btn btn-sm" style="border-radius:8px;border:1px solid #e2e8f0;color:#64748b;padding:.25rem .6rem;font-size:.75rem"
                            data-bs-toggle="modal" data-bs-target="#detailModal{{ $v->id }}">
                            <i class="bi bi-eye"></i>
                        </button>
                        <form method="POST" action="{{ route('vulnerabilities.destroy', $v) }}" class="d-inline"
                            onsubmit="return confirm('Delete this vulnerability?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm" style="border-radius:8px;border:1px solid #fca5a5;color:#dc2626;background:#fff8f8;padding:.25rem .6rem;font-size:.75rem">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>

                {{-- Detail Modal --}}
                <div class="modal fade" id="detailModal{{ $v->id }}" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content" style="border-radius:14px;border:1px solid #e8f5c2">
                            <div class="modal-header" style="border-bottom:2px solid var(--primary);padding:1rem 1.5rem">
                                <h5 class="modal-title" style="font-size:.95rem;font-weight:700;color:#0f172a">
                                    <span class="badge-sev {{ $sevClass }} me-2">{{ $v->severity }}</span>
                                    {{ $v->title }}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body" style="padding:1.5rem;font-size:.875rem">
                                <div class="row g-3 mb-3">
                                    <div class="col-6">
                                        <div style="font-size:.72rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Vulnerability ID</div>
                                        <div style="font-weight:600;font-family:monospace">{{ $v->vuln_id ?? '—' }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div style="font-size:.72rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Affected Asset</div>
                                        <div style="font-weight:600">{{ $v->asset }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div style="font-size:.72rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Status</div>
                                        <span class="badge-sev {{ $stClass }}">{{ $v->status }}</span>
                                    </div>
                                    <div class="col-6">
                                        <div style="font-size:.72rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Source File</div>
                                        <div>{{ $v->source_file ?? '—' }}</div>
                                    </div>
                                </div>
                                @if($v->description)
                                <div style="margin-bottom:1rem">
                                    <div style="font-size:.72rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.4rem">Description</div>
                                    <div style="background:#f8fafc;border-radius:8px;padding:.75rem 1rem;color:#374151;line-height:1.6">{{ $v->description }}</div>
                                </div>
                                @endif
                                @if($v->recommendation)
                                <div>
                                    <div style="font-size:.72rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.4rem">Remediation Recommendation</div>
                                    <div style="background:#f0fdf4;border-left:4px solid var(--primary);border-radius:0 8px 8px 0;padding:.75rem 1rem;color:#374151;line-height:1.6">{{ $v->recommendation }}</div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:3rem;color:#94a3b8">
                        <i class="bi bi-bug" style="font-size:2rem;display:block;margin-bottom:.75rem;opacity:.4"></i>
                        No vulnerabilities found. Upload a scan to get started.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($vulnerabilities->hasPages())
    <div style="padding:.75rem 1.5rem;border-top:1px solid #f1f5f9">
        {{ $vulnerabilities->links() }}
    </div>
    @endif
</div>

{{-- Upload Modal --}}
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:14px;border:1px solid #e8f5c2">
            <div class="modal-header" style="border-bottom:2px solid var(--primary);padding:1rem 1.5rem">
                <h5 class="modal-title" style="font-size:.95rem;font-weight:700;color:#0f172a">
                    <i class="bi bi-upload me-2" style="color:var(--primary)"></i>Upload Vulnerability Scan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('vulnerabilities.upload') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body" style="padding:1.5rem">
                    @if($errors->any())
                    <div class="alert alert-danger" style="font-size:.85rem;border-radius:8px">
                        {{ $errors->first() }}
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label" style="font-size:.82rem;font-weight:600;color:#374151">Scan File <span style="color:#dc2626">*</span></label>
                        <input type="file" name="scan_file" class="form-control" accept=".csv,.json,.txt" required style="border-radius:8px;font-size:.875rem">
                        <div style="font-size:.75rem;color:#94a3b8;margin-top:.4rem">
                            Supported: <strong>CSV</strong>, <strong>JSON</strong>. Max 10 MB.<br>
                            Expected columns: <code>vuln_id, severity, asset, title, description, recommendation</code>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #e8f5c2;padding:.75rem 1.5rem">
                    <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
                        style="border:1.5px solid #cbd5e1;border-radius:8px;color:#64748b;background:#fff;font-weight:500">Cancel</button>
                    <button type="submit" class="btn btn-sm"
                        style="background:var(--primary);color:#fff;border-radius:8px;font-weight:600;border:none;padding:.45rem 1.2rem">
                        <i class="bi bi-cloud-upload me-1"></i> Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ csp_nonce() }}">
// Re-open upload modal if there are validation errors
@if($errors->any())
new bootstrap.Modal(document.getElementById('uploadModal')).show();
@endif
</script>
@endpush

@endsection
