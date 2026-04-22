@extends('layouts.app')
@section('title', 'SLA Policies')

@section('content')
<style>
    :root { --lime: rgb(152,194,10); --lime-dark: rgb(118,151,7); --lime-muted: rgb(232,244,195); }
    .sla-card {
        background:#fff; border:1px solid #e8f5c2; border-radius:12px;
        padding:1.1rem 1.25rem; transition:box-shadow .15s, border-color .15s;
    }
    .sla-card:hover { box-shadow:0 4px 18px rgba(118,151,7,.12); border-color:var(--lime); }
    .sev-pill { display:inline-flex; align-items:center; gap:.28rem; padding:.15rem .5rem;
        border-radius:20px; font-size:.68rem; font-weight:700; }
</style>

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size:.73rem">
                <li class="breadcrumb-item active" style="color:#64748b">SLA Policies</li>
            </ol>
        </nav>
        <h5 style="margin:0;font-weight:700;color:#0f172a">
            <i class="bi bi-stopwatch-fill me-2" style="color:var(--lime)"></i>SLA Policies
        </h5>
        <p class="mb-0 mt-1" style="font-size:.82rem;color:#64748b">
            Define remediation timeframes by severity for vulnerability assessments.
        </p>
    </div>
    <a href="{{ route('sla-policies.create') }}" class="btn btn-sm"
        style="background:var(--lime);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.45rem 1.1rem">
        <i class="bi bi-plus-lg me-1"></i>New Policy
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3" style="border-radius:10px;font-size:.875rem">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Search --}}
<form method="GET" class="mb-3 d-flex gap-2">
    <div class="input-group input-group-sm" style="max-width:320px">
        <span class="input-group-text" style="border-radius:8px 0 0 8px;background:#f8fafc"><i class="bi bi-search"></i></span>
        <input type="text" name="search" class="form-control" placeholder="Search policies…"
            value="{{ request('search') }}" style="border-radius:0 8px 8px 0">
    </div>
    @if(request('search'))
    <a href="{{ route('sla-policies.index') }}" class="btn btn-sm"
        style="border:1.5px solid #cbd5e1;border-radius:8px;color:#64748b;background:#fff">
        <i class="bi bi-x"></i>
    </a>
    @endif
</form>

{{-- Policies list --}}
@forelse($policies as $p)
<div class="sla-card mb-2">
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
        <div style="min-width:0;flex:1">
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                <span style="width:32px;height:32px;border-radius:8px;background:var(--lime-muted);
                              display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="bi bi-stopwatch-fill" style="color:var(--lime-dark);font-size:.85rem"></i>
                </span>
                <span style="font-weight:700;color:#0f172a;font-size:.92rem">{{ $p->name }}</span>
                @if($p->is_default)
                <span style="font-size:.66rem;font-weight:700;background:var(--lime-muted);color:var(--lime-dark);border-radius:20px;padding:.1rem .5rem">
                    <i class="bi bi-star-fill me-1"></i>Default
                </span>
                @endif
                <span style="font-size:.7rem;color:#94a3b8;background:#f1f5f9;border-radius:20px;padding:.1rem .5rem">
                    {{ $p->assessments_count }} assessment{{ $p->assessments_count !== 1 ? 's' : '' }}
                </span>
            </div>
            @if($p->description)
            <div style="font-size:.78rem;color:#64748b;margin-left:2.75rem;margin-bottom:.5rem">{{ $p->description }}</div>
            @endif
            {{-- SLA days breakdown --}}
            <div class="d-flex flex-wrap gap-2 ms-1" style="margin-left:2.75rem">
                <span class="sev-pill" style="background:#fee2e2;color:#991b1b">
                    <i class="bi bi-exclamation-octagon-fill" style="font-size:.65rem"></i>
                    Critical: {{ $p->critical_days }}d
                </span>
                <span class="sev-pill" style="background:#ffedd5;color:#9a3412">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size:.65rem"></i>
                    High: {{ $p->high_days }}d
                </span>
                <span class="sev-pill" style="background:#fef9c3;color:#854d0e">
                    <i class="bi bi-dash-circle-fill" style="font-size:.65rem"></i>
                    Medium: {{ $p->medium_days }}d
                </span>
                <span class="sev-pill" style="background:#f1f5f9;color:#475569">
                    <i class="bi bi-info-circle-fill" style="font-size:.65rem"></i>
                    Low: {{ $p->low_days }}d
                </span>
            </div>
            <div style="font-size:.7rem;color:#94a3b8;margin-left:2.75rem;margin-top:.35rem">
                <i class="bi bi-person me-1"></i>Created by {{ $p->creator?->name ?? '—' }}
                &nbsp;·&nbsp;
                <i class="bi bi-calendar3 me-1"></i>{{ $p->created_at->format('d M Y') }}
            </div>
        </div>
        <div class="d-flex gap-1 flex-shrink-0 align-self-center">
            <a href="{{ route('sla-policies.edit', $p) }}"
               class="btn btn-sm" style="border-radius:8px;border:1.5px solid var(--lime);color:var(--lime-dark);
                      background:var(--lime-muted);padding:.3rem .65rem;font-size:.78rem">
                <i class="bi bi-pencil"></i>
            </a>
            <form method="POST" action="{{ route('sla-policies.destroy', $p) }}" class="d-inline"
                  onsubmit="return confirm('Delete policy \'{{ $p->name }}\'?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm"
                    style="border-radius:8px;border:1px solid #fca5a5;color:#dc2626;background:#fff8f8;padding:.3rem .65rem;font-size:.78rem"
                    {{ $p->assessments_count > 0 ? 'disabled title=Cannot delete: policy is in use' : '' }}>
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        </div>
    </div>
</div>
@empty
<div style="background:#fff;border:2px dashed #e8f5c2;border-radius:14px;padding:4rem 2rem;text-align:center;color:#94a3b8">
    <i class="bi bi-stopwatch" style="font-size:2.5rem;display:block;margin-bottom:.75rem;opacity:.3;color:var(--lime)"></i>
    <div style="font-weight:600;font-size:1rem;color:#64748b;margin-bottom:.4rem">No SLA policies yet</div>
    <p style="font-size:.83rem;margin-bottom:1.2rem">Create a policy to track remediation deadlines by severity.</p>
    <a href="{{ route('sla-policies.create') }}" class="btn btn-sm"
       style="background:var(--lime);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.45rem 1.4rem">
        <i class="bi bi-plus-lg me-1"></i>Create First Policy
    </a>
</div>
@endforelse

@if($policies->hasPages())
<div class="d-flex justify-content-center mt-3">{{ $policies->links() }}</div>
@endif

@endsection
