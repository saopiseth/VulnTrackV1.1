@extends('layouts.app')
@section('title', 'Project Assessments')

@section('content')

{{-- Page Header --}}
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4>Project Assessments</h4>
        <p>Manage and track all security assessments across projects.</p>
    </div>
    <a href="{{ route('assessments.create') }}" class="btn btn-primary-act">
        <i class="bi bi-plus-lg me-1"></i> New Assessment
    </a>
</div>

<style>
    .btn-primary-act {
        background: rgb(152,194,10);
        color:#fff; border:none; border-radius:9px;
        font-weight:600; font-size:.875rem; padding:.5rem 1.1rem;
        text-decoration:none; display:inline-flex; align-items:center;
        box-shadow:0 4px 12px rgba(152,194,10,.35);
        transition: all .2s;
    }
    .btn-primary-act:hover { color:#fff; transform:translateY(-1px); box-shadow:0 6px 18px rgba(152,194,10,.45); }

    /* Stat widgets */
    .stat-mini { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1rem 1.25rem; }
    .stat-mini .sm-val { font-size:1.5rem; font-weight:800; color:#0f172a; }
    .stat-mini .sm-lbl { font-size:.75rem; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:.5px; }

    /* Table */
    .assess-table { border-radius:14px; overflow:hidden; border:1px solid #e2e8f0; background:#fff; }
    .assess-table table { margin:0; font-size:.82rem; }
    .assess-table thead th {
        background:#f8fafc; color:#64748b; font-size:.72rem; font-weight:700;
        text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid #e2e8f0;
        padding:.75rem 1rem; white-space:nowrap;
    }
    .assess-table tbody td { padding:.7rem 1rem; vertical-align:middle; border-color:#f1f5f9; color:#374151; }
    .assess-table tbody tr:hover { background:#fafbff; }

    /* Badges */
    .badge-status, .badge-priority, .badge-check {
        padding:.22rem .65rem; border-radius:20px; font-size:.72rem; font-weight:700;
        display:inline-block; white-space:nowrap;
    }
    .badge-open        { background:#fee2e2; color:#dc2626; }
    .badge-in-progress { background:#fef3c7; color:#d97706; }
    .badge-closed      { background:#d1fae5; color:#059669; }
    .badge-critical    { background:#fee2e2; color:#dc2626; }
    .badge-high        { background:#ffedd5; color:#ea580c; }
    .badge-medium      { background:#fef3c7; color:#d97706; }
    .badge-low         { background:#f0fdf4; color:#16a34a; }
    .badge-yes         { background:#d1fae5; color:#059669; }
    .badge-no          { background:#f1f5f9; color:#94a3b8; }

    /* Search bar */
    .filter-bar { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1rem 1.25rem; margin-bottom:1.25rem; }
    .filter-bar .form-control, .filter-bar .form-select {
        font-size:.85rem; border-color:#e2e8f0; border-radius:8px;
    }
    .filter-bar .form-control:focus, .filter-bar .form-select:focus {
        border-color:#4f46e5; box-shadow:0 0 0 3px rgba(79,70,229,.1);
    }

    /* Action buttons */
    .btn-act { padding:.25rem .55rem; border-radius:7px; font-size:.78rem; border:1px solid #e2e8f0; background:#fff; }
    .btn-act:hover { background:#f1f5f9; }

    /* Truncate long text */
    .text-truncate-150 { max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; display:block; }
</style>

{{-- Stat Row --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-mini">
            <div class="sm-val">{{ $stats['total'] }}</div>
            <div class="sm-lbl">Total</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-mini" style="border-left:3px solid #dc2626">
            <div class="sm-val" style="color:#dc2626">{{ $stats['open'] }}</div>
            <div class="sm-lbl">Open</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-mini" style="border-left:3px solid #d97706">
            <div class="sm-val" style="color:#d97706">{{ $stats['in_progress'] }}</div>
            <div class="sm-lbl">In Progress</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-mini" style="border-left:3px solid #059669">
            <div class="sm-val" style="color:#059669">{{ $stats['closed'] }}</div>
            <div class="sm-lbl">Closed</div>
        </div>
    </div>
</div>

{{-- Alerts --}}
@if(session('success'))
    <div class="alert d-flex align-items-center gap-2 mb-3" style="background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;border-radius:10px;font-size:.875rem">
        <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
    </div>
@endif

{{-- Filter Bar --}}
<div class="filter-bar">
    <form method="GET" action="{{ route('assessments.index') }}" class="row g-2 align-items-end">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text" style="background:#f8fafc;border-color:#e2e8f0"><i class="bi bi-search" style="color:#94a3b8"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search project name, coordinator, BCD ID..." value="{{ request('search') }}">
            </div>
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="">All Status</option>
                @foreach(['Open','In Progress','Closed'] as $s)
                    <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="priority" class="form-select">
                <option value="">All Priority</option>
                @foreach(['Critical','High','Medium','Low'] as $p)
                    <option value="{{ $p }}" {{ request('priority') == $p ? 'selected' : '' }}>{{ $p }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary-act flex-fill">
                <i class="bi bi-funnel me-1"></i>Filter
            </button>
            <a href="{{ route('assessments.index') }}" class="btn btn-act flex-fill text-center">Clear</a>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="assess-table">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Project Name</th>
                    <th>Kickoff</th>
                    <th>Due Date</th>
                    <th>Complete Date</th>
                    <th>Coordinator</th>
                    <th>Priority</th>
                    <th>BCD ID</th>
                    <th title="Applicable Criteria count">Criteria</th>
                    <th>Status</th>
                    <th>BCD URL</th>
                    <th>Comments</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assessments as $a)
                <tr>
                    <td style="color:#94a3b8;font-size:.75rem">{{ $a->id }}</td>
                    <td>
                        <a href="{{ route('assessments.show', $a) }}" style="color:#0f172a;font-weight:600;text-decoration:none">
                            <span class="text-truncate-150">{{ $a->assessment_type }}</span>
                        </a>
                    </td>
                    <td style="white-space:nowrap">{{ $a->project_kickoff?->format('d M Y') ?? '—' }}</td>
                    <td style="white-space:nowrap">
                        @if($a->due_date)
                            <span style="{{ $a->due_date->isPast() && $a->status !== 'Closed' ? 'color:#dc2626;font-weight:600' : '' }}">
                                {{ $a->due_date->format('d M Y') }}
                            </span>
                        @else —
                        @endif
                    </td>
                    <td style="white-space:nowrap">{{ $a->complete_date?->format('d M Y') ?? '—' }}</td>
                    <td>{{ $a->project_coordinator ?? '—' }}</td>
                    <td>
                        @php $pc = strtolower($a->priority); @endphp
                        <span class="badge-priority badge-{{ $pc }}">{{ $a->priority }}</span>
                    </td>
                    <td style="font-family:monospace;font-size:.78rem">{{ $a->bcd_id ?? '—' }}</td>
                    <td>
                        @php
                            $fields = ['system_architecture_review','penetration_test','security_hardening','vulnerability_assessment','secure_code_review','antimalware_protection','network_security','security_monitoring','system_access_matrix'];
                            $applicable = collect($fields)->filter(fn($f) => $a->$f)->count();
                        @endphp
                        <span class="badge-check {{ $applicable > 0 ? 'badge-yes' : 'badge-no' }}">
                            {{ $applicable }}/9
                        </span>
                    </td>
                    <td>
                        @php $sc = str_replace(' ','-',strtolower($a->status)); @endphp
                        <span class="badge-status badge-{{ $sc }}">{{ $a->status }}</span>
                    </td>
                    <td>
                        @if($a->bcd_url)
                            <a href="{{ $a->bcd_url }}" target="_blank" class="btn-act btn" style="font-size:.75rem;padding:.2rem .5rem">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                        @else
                            <span style="color:#cbd5e1">—</span>
                        @endif
                    </td>
                    <td>
                        @if($a->comments)
                            <span class="text-truncate-150" title="{{ $a->comments }}">{{ $a->comments }}</span>
                        @else
                            <span style="color:#cbd5e1">—</span>
                        @endif
                    </td>
                    <td style="white-space:nowrap">
                        <a href="{{ route('assessments.show', $a) }}" class="btn btn-act" title="View">
                            <i class="bi bi-eye" style="color:#4f46e5"></i>
                        </a>
                        @can('update', $a)
                        <a href="{{ route('assessments.edit', $a) }}" class="btn btn-act ms-1" title="Edit">
                            <i class="bi bi-pencil" style="color:#d97706"></i>
                        </a>
                        @endcan
                        @can('delete', $a)
                        <form method="POST" action="{{ route('assessments.destroy', $a) }}" class="d-inline ms-1"
                              onsubmit="return confirm('Delete this assessment?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-act" title="Delete">
                                <i class="bi bi-trash" style="color:#dc2626"></i>
                            </button>
                        </form>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="17" class="text-center py-5" style="color:#94a3b8">
                        <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:.5rem"></i>
                        No assessments found.
                        <a href="{{ route('assessments.create') }}" style="color:#4f46e5;font-weight:600">Create one</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($assessments->hasPages())
    <div class="d-flex justify-content-between align-items-center px-4 py-3" style="border-top:1px solid #f1f5f9">
        <p class="mb-0" style="font-size:.8rem;color:#64748b">
            Showing {{ $assessments->firstItem() }}–{{ $assessments->lastItem() }} of {{ $assessments->total() }} records
        </p>
        {{ $assessments->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>

@endsection
