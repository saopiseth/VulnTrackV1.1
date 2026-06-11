@extends('layouts.app')

@section('title', 'Segmentation Tests')

@section('content')
<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active">Segmentation Tests</li>
            </ol>
        </nav>
        <h4><i class="bi bi-diagram-3-fill me-2" style="color:var(--primary)"></i>Segmentation Tests</h4>
        <p>Upload Nmap scan results to assess network segmentation effectiveness between subnets.</p>
    </div>
    <a href="{{ route('segmentation.create') }}"
       class="btn px-4 flex-shrink-0" style="background:var(--primary);color:#fff">
        <i class="bi bi-plus-lg me-1"></i>New Test
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<style>
.test-card {
    background:#fff; border:1px solid var(--border); border-radius:14px;
    padding:1.35rem 1.5rem; margin-bottom:1rem;
    box-shadow:0 1px 4px rgba(0,0,0,.04);
    display:flex; align-items:center; gap:1.25rem;
    transition:box-shadow .2s;
}
.test-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.08); }
.test-icon {
    width:48px; height:48px; border-radius:13px; flex-shrink:0;
    background:color-mix(in srgb,var(--primary) 14%,white);
    display:flex; align-items:center; justify-content:center;
    font-size:1.3rem; color:var(--primary-dark);
}
.test-name { font-weight:700; color:#0f172a; font-size:.95rem; margin-bottom:.15rem; }
.test-meta { font-size:.78rem; color:var(--text-muted); }
.badge-accessible { background:#dcfce7; color:#16a34a; }
.badge-processing  { background:#fef9c3; color:#a16207; }
.badge-failed      { background:#fee2e2; color:#dc2626; }
.badge-pending     { background:#f1f5f9; color:#64748b; }
</style>

@if($tests->isEmpty())
<div class="card p-5 text-center">
    <i class="bi bi-diagram-3" style="font-size:3rem;color:var(--border)"></i>
    <div class="mt-3 fw-600" style="color:#64748b">No segmentation tests yet</div>
    <div class="text-muted mt-1" style="font-size:.875rem">Upload an Nmap scan to analyse network segmentation.</div>
    <a href="{{ route('segmentation.create') }}" class="btn mt-3 px-4"
       style="background:var(--primary);color:#fff">
        <i class="bi bi-plus-lg me-1"></i>Run First Test
    </a>
</div>
@else

{{-- Stats row --}}
@php
    $allTests      = $tests->getCollection();
    $completedAll  = \App\Models\SegmentationTest::where('upload_status','completed')->count();
    $totalSubnets  = \App\Models\SegmentationResult::count();
    $accessible    = \App\Models\SegmentationResult::where('status','accessible')->count();
    $notAccessible = \App\Models\SegmentationResult::where('status','not_accessible')->count();
@endphp
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card p-3 text-center">
            <div style="font-size:1.8rem;font-weight:800;color:#0f172a">{{ $tests->total() }}</div>
            <div style="font-size:.78rem;color:var(--text-muted)">Total Tests</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card p-3 text-center">
            <div style="font-size:1.8rem;font-weight:800;color:#0f172a">{{ $totalSubnets }}</div>
            <div style="font-size:.78rem;color:var(--text-muted)">Subnets Analysed</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card p-3 text-center">
            <div style="font-size:1.8rem;font-weight:800;color:#16a34a">{{ $accessible }}</div>
            <div style="font-size:.78rem;color:var(--text-muted)">Accessible</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card p-3 text-center">
            <div style="font-size:1.8rem;font-weight:800;color:#ef4444">{{ $notAccessible }}</div>
            <div style="font-size:.78rem;color:var(--text-muted)">Not Accessible</div>
        </div>
    </div>
</div>

{{-- Test list --}}
@foreach($tests as $test)
@php
    $badgeClass = match($test->upload_status) {
        'completed'  => 'badge-accessible',
        'processing' => 'badge-processing',
        'failed'     => 'badge-failed',
        default      => 'badge-pending',
    };
    $badgeLabel = match($test->upload_status) {
        'completed'  => 'Completed',
        'processing' => 'Processing…',
        'failed'     => 'Failed',
        default      => 'Pending',
    };
@endphp
<div class="test-card">
    <div class="test-icon"><i class="bi bi-diagram-3-fill"></i></div>
    <div class="flex-grow-1 min-w-0">
        <div class="test-name text-truncate">{{ $test->name }}</div>
        <div class="test-meta">
            Scanner: <strong>{{ $test->scanner_ip ?? '—' }}</strong>
            &bull; {{ $test->results_count }} subnet(s) analysed
            &bull; Uploaded {{ $test->created_at->diffForHumans() }}
            @if($test->creator)
                &bull; by {{ $test->creator->name }}
            @endif
        </div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-shrink-0">
        <span class="badge rounded-pill px-3 py-1 {{ $badgeClass }}" style="font-size:.75rem;font-weight:600">
            @if($test->upload_status === 'processing')
                <span class="spinner-border spinner-border-sm me-1" style="width:.6rem;height:.6rem"></span>
            @endif
            {{ $badgeLabel }}
        </span>
        <a href="{{ route('segmentation.show', $test) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-eye me-1"></i>View
        </a>
    </div>
</div>
@endforeach

{{-- Pagination --}}
<div class="d-flex justify-content-end mt-3">
    {{ $tests->links() }}
</div>
@endif
@endsection
