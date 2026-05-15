@extends('layouts.app')
@section('title', 'Upload Scans — ' . $assessment->name)

@section('content')
<style>
    :root { --lime: var(--primary); --lime-dark: var(--primary-dark); --lime-muted: rgb(232,244,195); }

    .stat-strip { background:#fff; border:1px solid #e8f5c2; border-radius:10px; padding:.7rem 1.1rem; text-align:center; }
    .stat-strip .lbl { font-size:.62rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; margin-bottom:.1rem; }
    .stat-strip .val { font-size:1.55rem; font-weight:800; line-height:1.15; }

    .va-card { background:#fff; border:1px solid #e8f5c2; border-radius:12px; padding:1.25rem 1.35rem; margin-bottom:1rem; }

    .badge-env { padding:.22rem .7rem; border-radius:20px; font-size:.7rem; font-weight:700; display:inline-block; }
    .env-production  { background:#fee2e2; color:#991b1b; }
    .env-uat         { background:#fef9c3; color:#854d0e; }
    .env-internal    { background:#e0f2fe; color:#0c4a6e; }
    .env-development { background:#f1f5f9; color:#475569; }

    /* Upload card drop zone hover */
    .drop-hover-blue { background:#dbeafe !important; border-color:#3b82f6 !important; }
    .drop-hover-green { background:#dcfce7 !important; border-color:#22c55e !important; }
</style>

{{-- ── Page header ── --}}
<div style="background:linear-gradient(135deg,#f8fafc 0%,#eff6ff 100%);
            border:1px solid #bfdbfe;border-radius:14px;padding:1.25rem 1.75rem;margin-bottom:1.25rem">

    {{-- Breadcrumb --}}
    <nav style="margin-bottom:.55rem">
        <ol class="breadcrumb mb-0" style="font-size:.73rem">
            <li class="breadcrumb-item">
                <a href="{{ route('vuln-assessments.index') }}" style="color:#94a3b8;text-decoration:none">VA Assessments</a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('vuln-assessments.show', $assessment) }}" style="color:#94a3b8;text-decoration:none">
                    {{ Str::limit($assessment->name, 40) }}
                </a>
            </li>
            <li class="breadcrumb-item active" style="color:#1e40af">Upload Scans</li>
        </ol>
    </nav>

    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#2563eb,#1d4ed8);
                            display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="bi bi-cloud-upload-fill" style="color:#fff;font-size:.95rem"></i>
                </div>
                <div>
                    <h4 style="color:#0f172a;margin:0;font-size:1.15rem;font-weight:700">Upload Scans</h4>
                    <div style="font-size:.78rem;color:#3b82f6;margin-top:.05rem">{{ $assessment->name }}</div>
                </div>
                @if($assessment->environment)
                <span class="badge-env env-{{ strtolower($assessment->environment) }}">{{ $assessment->environment }}</span>
                @endif
            </div>
        </div>

        <div class="d-flex gap-2 flex-wrap align-items-center flex-shrink-0">
            <a href="{{ route('vuln-assessments.show', $assessment) }}" class="btn btn-sm"
               style="border:1.5px solid #e2e8f0;border-radius:8px;color:#374151;background:#fff;
                      font-weight:500;font-size:.81rem;padding:.38rem .9rem;text-decoration:none">
                <i class="bi bi-arrow-left me-1"></i>Overview
            </a>
            @if($assessment->scans->count())
            <a href="{{ route('vuln-assessments.findings', $assessment) }}" class="btn btn-sm"
               style="border:1.5px solid var(--lime);border-radius:8px;color:var(--lime-dark);background:#fff;
                      font-weight:600;font-size:.81rem;padding:.38rem .9rem;text-decoration:none">
                <i class="bi bi-table me-1"></i>Findings
            </a>
            <a href="{{ route('vuln-assessments.progress', $assessment) }}" class="btn btn-sm"
               style="border:1.5px solid #e2e8f0;border-radius:8px;color:#374151;background:#fff;
                      font-weight:500;font-size:.81rem;padding:.38rem .9rem;text-decoration:none">
                <i class="bi bi-bar-chart-line me-1"></i>Progress
            </a>
            @endif
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3" style="border-radius:10px;font-size:.875rem">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- ── Tracking statistics strip ── --}}
@if($trackingStats)
<div class="row g-2 mb-3">
    <div class="col-6 col-sm-4 col-md">
        <div class="stat-strip">
            <div class="lbl" style="color:#94a3b8">Total Tracked</div>
            <div class="val" style="color:#0f172a">{{ number_format($trackingStats->total) }}</div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-md">
        <div class="stat-strip" style="border-color:#fca5a5;background:#fff8f8">
            <div class="lbl" style="color:#991b1b">Open</div>
            <div class="val" style="color:#dc2626">{{ number_format($trackingStats->open_count) }}</div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-md">
        <div class="stat-strip" style="border-color:#bbf7d0;background:#f0fdf4">
            <div class="lbl" style="color:#059669">Resolved</div>
            <div class="val" style="color:#16a34a">{{ number_format($trackingStats->resolved) }}</div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-md">
        <div class="stat-strip" style="border-color:#c7d2fe;background:#eef2ff">
            <div class="lbl" style="color:#4338ca">New</div>
            <div class="val" style="color:#4f46e5">{{ number_format($trackingStats->new_count) }}</div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-md">
        <div class="stat-strip" style="border-color:#fed7aa;background:#fff7ed">
            <div class="lbl" style="color:#c2410c">Reopened</div>
            <div class="val" style="color:#ea580c">{{ number_format($trackingStats->reopened) }}</div>
        </div>
    </div>
</div>
@endif

{{-- ── Two upload cards ── --}}
<div class="row g-3">

{{-- ────────────────────────────────────────────────────────────────
     Initial Scan Card
──────────────────────────────────────────────────────────────── --}}
<div class="col-12 col-xl-6">
<div class="va-card" style="border-color:#bfdbfe">

    <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;
                padding-bottom:1rem;border-bottom:2px solid #dbeafe">
        <div style="width:42px;height:42px;border-radius:11px;
                    background:linear-gradient(135deg,#2563eb,#1d4ed8);
                    display:flex;align-items:center;justify-content:center;flex-shrink:0;
                    box-shadow:0 3px 8px rgba(37,99,235,.3)">
            <i class="bi bi-cloud-upload-fill" style="color:#fff;font-size:1.05rem"></i>
        </div>
        <div style="flex:1;min-width:0">
            <div style="font-size:.95rem;font-weight:700;color:#1e3a8a">Initial Scan Upload</div>
            <div style="font-size:.74rem;color:#3b82f6;margin-top:.1rem">Baseline scan — imports all findings as Open</div>
        </div>
        <span style="background:#dbeafe;color:#1d4ed8;padding:.2rem .7rem;border-radius:20px;
                     font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;flex-shrink:0">
            {{ $initialScans->count() }} scan{{ $initialScans->count() !== 1 ? 's' : '' }}
        </span>
    </div>

    {{-- Form --}}
    <div id="init-form-wrap">

        <div id="init-dropzone"
             style="border:2px dashed #93c5fd;border-radius:10px;padding:1.2rem;
                    text-align:center;cursor:pointer;margin-bottom:.65rem;background:#f0f7ff;
                    transition:background .15s,border-color .15s"
             onclick="document.getElementById('init-file-input').click()">
            <i class="bi bi-file-earmark-bar-graph"
               style="font-size:1.5rem;color:#3b82f6;display:block;margin-bottom:.35rem"></i>
            <div style="font-size:.82rem;font-weight:600;color:#1e40af">Drop .nessus file here or click to browse</div>
            <div id="init-file-name" style="font-size:.74rem;color:#64748b;margin-top:.2rem">No file selected</div>
            <input type="file" id="init-file-input" accept=".xml,.nessus,.csv" style="display:none">
        </div>

        <div class="mb-2">
            <label style="font-size:.77rem;font-weight:600;color:#374151;display:block;margin-bottom:.3rem">Remarks</label>
            <input type="text" id="init-remarks" class="form-control form-control-sm"
                   placeholder="Optional notes about this scan"
                   style="border-radius:8px;border-color:#bfdbfe;font-size:.83rem">
        </div>

        <div id="init-progress-wrap" style="display:none;margin-bottom:.65rem">
            <div style="display:flex;justify-content:space-between;margin-bottom:.3rem">
                <span id="init-progress-label" style="font-size:.78rem;font-weight:600;color:#1e40af">Uploading…</span>
                <span id="init-progress-pct" style="font-size:.74rem;color:#64748b;font-weight:600">0%</span>
            </div>
            <div style="height:7px;border-radius:20px;background:#dbeafe;overflow:hidden">
                <div id="init-progress-bar"
                     style="height:100%;width:0%;border-radius:20px;
                            background:linear-gradient(90deg,#3b82f6,#1d4ed8);transition:width .3s ease"></div>
            </div>
        </div>

        <div id="init-alert" style="display:none;font-size:.8rem;border-radius:8px;padding:.5rem .85rem;margin-bottom:.65rem"></div>

        <button id="init-upload-btn" class="btn btn-sm w-100"
                style="background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border:none;
                       border-radius:9px;font-weight:600;padding:.48rem;font-size:.84rem;
                       box-shadow:0 2px 8px rgba(37,99,235,.25)">
            <i class="bi bi-cloud-upload me-1"></i>Import Initial Scan
        </button>
    </div>

    {{-- History --}}
    @if($initialScans->count())
    <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid #dbeafe">
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;
                    color:#1e40af;margin-bottom:.75rem;display:flex;align-items:center;gap:.4rem">
            <i class="bi bi-clock-history"></i>Upload History
        </div>
        <div class="table-responsive">
        <table class="table table-sm mb-0" style="font-size:.79rem">
            <thead>
            <tr style="border-bottom:2px solid #dbeafe">
                <th style="font-size:.68rem;font-weight:700;color:#64748b;padding:.3rem 0;border:none;text-transform:uppercase;letter-spacing:.4px">File</th>
                <th style="font-size:.68rem;font-weight:700;color:#64748b;padding:.3rem .4rem;border:none;text-transform:uppercase;letter-spacing:.4px">By</th>
                <th class="text-end" style="font-size:.68rem;font-weight:700;color:#64748b;padding:.3rem .4rem;border:none;text-transform:uppercase;letter-spacing:.4px">Findings</th>
                <th class="text-end" style="font-size:.68rem;font-weight:700;color:#64748b;padding:.3rem .4rem;border:none;text-transform:uppercase;letter-spacing:.4px">Status</th>
                <th style="border:none;padding:.3rem 0"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($initialScans as $scan)
            <tr style="border-bottom:1px solid #f1f5f9">
                <td style="padding:.5rem 0;vertical-align:middle">
                    <div style="color:#475569;font-size:.79rem;font-family:monospace">{{ Str::limit($scan->filename, 40) }}</div>
                    @if($scan->is_baseline)
                    <span style="background:var(--lime-muted);color:var(--lime-dark);padding:.08rem .4rem;border-radius:20px;font-size:.64rem;font-weight:700">Baseline</span>
                    @endif
                </td>
                <td style="padding:.5rem .4rem;vertical-align:middle;color:#475569;font-size:.79rem">
                    {{ Str::limit($scan->creator?->name ?? '—', 18) }}
                </td>
                <td class="text-end" style="padding:.5rem .4rem;vertical-align:middle">
                    @if($scan->isCompleted())
                    <span style="font-weight:700;color:#0f172a">{{ number_format($scan->finding_count) }}</span>
                    <div style="font-size:.68rem;color:#94a3b8">{{ $scan->host_count }} host{{ $scan->host_count !== 1 ? 's' : '' }}</div>
                    @else — @endif
                </td>
                <td class="text-end" style="padding:.5rem .4rem;vertical-align:middle">
                    @if($scan->isCompleted())
                    <span style="background:#d1fae5;color:#065f46;padding:.1rem .5rem;border-radius:20px;font-size:.67rem;font-weight:700">Done</span>
                    @elseif($scan->isFailed())
                    <span style="background:#fee2e2;color:#991b1b;padding:.1rem .5rem;border-radius:20px;font-size:.67rem;font-weight:700"
                          title="{{ $scan->upload_error }}">Failed</span>
                    @else
                    <span style="background:#fef3c7;color:#92400e;padding:.1rem .5rem;border-radius:20px;font-size:.67rem;font-weight:700">Processing</span>
                    @endif
                </td>
                <td style="padding:.5rem 0;vertical-align:middle;text-align:right">
                    <form method="POST"
                          action="{{ route('vuln-assessments.scans.destroy', [$assessment, $scan]) }}"
                          onsubmit="return confirm('Delete this scan?\n\nThis removes all {{ number_format($scan->finding_count) }} findings and cannot be undone.')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                style="background:#fef2f2;border:1px solid #fecaca;border-radius:7px;color:#dc2626;
                                       padding:.18rem .42rem;font-size:.72rem;cursor:pointer">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
    </div>
    @endif

</div>
</div>

{{-- ────────────────────────────────────────────────────────────────
     Verification Scan Card
──────────────────────────────────────────────────────────────── --}}
<div class="col-12 col-xl-6">
<div class="va-card" style="border-color:#bbf7d0">

    <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;
                padding-bottom:1rem;border-bottom:2px solid #bbf7d0">
        <div style="width:42px;height:42px;border-radius:11px;
                    background:linear-gradient(135deg,#16a34a,#059669);
                    display:flex;align-items:center;justify-content:center;flex-shrink:0;
                    box-shadow:0 3px 8px rgba(22,163,74,.3)">
            <i class="bi bi-patch-check-fill" style="color:#fff;font-size:1.05rem"></i>
        </div>
        <div style="flex:1;min-width:0">
            <div style="font-size:.95rem;font-weight:700;color:#064e3b">Verification Scan Upload</div>
            <div style="font-size:.74rem;color:#16a34a;margin-top:.1rem">Retest scan — resolves fixed vulnerabilities automatically</div>
        </div>
        <span style="background:#d1fae5;color:#065f46;padding:.2rem .7rem;border-radius:20px;
                     font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;flex-shrink:0">
            {{ $verificationScans->count() }} scan{{ $verificationScans->count() !== 1 ? 's' : '' }}
        </span>
    </div>

    @if($initialScans->count() === 0)
    <div style="background:#fef9c3;border:1px solid #fde047;border-radius:10px;
                padding:1.5rem;text-align:center;margin-bottom:1rem">
        <i class="bi bi-shield-exclamation" style="font-size:1.8rem;color:#d97706;display:block;margin-bottom:.6rem"></i>
        <div style="font-size:.85rem;font-weight:700;color:#854d0e;margin-bottom:.3rem">No Initial Scan Found</div>
        <div style="font-size:.77rem;color:#92400e;line-height:1.6">
            Upload an initial scan first. Verification scans compare results against<br>
            existing tracked vulnerabilities to mark resolved ones automatically.
        </div>
    </div>
    @else
    <div id="verif-form-wrap">

        <div id="verif-dropzone"
             style="border:2px dashed #4ade80;border-radius:10px;padding:1.2rem;
                    text-align:center;cursor:pointer;margin-bottom:.65rem;background:#f0fdf4;
                    transition:background .15s,border-color .15s"
             onclick="document.getElementById('verif-file-input').click()">
            <i class="bi bi-file-earmark-check"
               style="font-size:1.5rem;color:#16a34a;display:block;margin-bottom:.35rem"></i>
            <div style="font-size:.82rem;font-weight:600;color:#065f46">Drop .nessus file here or click to browse</div>
            <div id="verif-file-name" style="font-size:.74rem;color:#64748b;margin-top:.2rem">No file selected</div>
            <input type="file" id="verif-file-input" accept=".xml,.nessus,.csv" style="display:none">
        </div>

        <div class="mb-2">
            <label style="font-size:.77rem;font-weight:600;color:#374151;display:block;margin-bottom:.3rem">Verification Remarks</label>
            <input type="text" id="verif-remarks" class="form-control form-control-sm"
                   placeholder="Optional notes about this verification"
                   style="border-radius:8px;border-color:#86efac;font-size:.83rem">
        </div>

        <div id="verif-progress-wrap" style="display:none;margin-bottom:.65rem">
            <div style="display:flex;justify-content:space-between;margin-bottom:.3rem">
                <span id="verif-progress-label" style="font-size:.78rem;font-weight:600;color:#059669">Uploading…</span>
                <span id="verif-progress-pct" style="font-size:.74rem;color:#64748b;font-weight:600">0%</span>
            </div>
            <div style="height:7px;border-radius:20px;background:#bbf7d0;overflow:hidden">
                <div id="verif-progress-bar"
                     style="height:100%;width:0%;border-radius:20px;
                            background:linear-gradient(90deg,#22c55e,#16a34a);transition:width .3s ease"></div>
            </div>
        </div>

        <div id="verif-alert" style="display:none;font-size:.8rem;border-radius:8px;padding:.5rem .85rem;margin-bottom:.65rem"></div>

        <button id="verif-upload-btn" class="btn btn-sm w-100"
                style="background:linear-gradient(135deg,#16a34a,#059669);color:#fff;border:none;
                       border-radius:9px;font-weight:600;padding:.48rem;font-size:.84rem;
                       box-shadow:0 2px 8px rgba(22,163,74,.25)">
            <i class="bi bi-patch-check me-1"></i>Import Verification Scan
        </button>
    </div>
    @endif

    {{-- History --}}
    @if($verificationScans->count())
    <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid #bbf7d0">
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;
                    color:#059669;margin-bottom:.75rem;display:flex;align-items:center;gap:.4rem">
            <i class="bi bi-clock-history"></i>Upload History
        </div>
        <div class="table-responsive">
        <table class="table table-sm mb-0" style="font-size:.79rem">
            <thead>
            <tr style="border-bottom:2px solid #bbf7d0">
                <th style="font-size:.68rem;font-weight:700;color:#64748b;padding:.3rem 0;border:none;text-transform:uppercase;letter-spacing:.4px">File</th>
                <th style="font-size:.68rem;font-weight:700;color:#64748b;padding:.3rem .4rem;border:none;text-transform:uppercase;letter-spacing:.4px">By</th>
                <th class="text-end" style="font-size:.68rem;font-weight:700;color:#64748b;padding:.3rem .4rem;border:none;text-transform:uppercase;letter-spacing:.4px">Findings</th>
                <th class="text-end" style="font-size:.68rem;font-weight:700;color:#64748b;padding:.3rem .4rem;border:none;text-transform:uppercase;letter-spacing:.4px">Status</th>
                <th style="border:none;padding:.3rem 0"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($verificationScans as $scan)
            <tr style="border-bottom:1px solid #f1f5f9">
                <td style="padding:.5rem 0;vertical-align:middle">
                    <div style="color:#475569;font-size:.79rem;font-family:monospace">{{ Str::limit($scan->filename, 40) }}</div>
                </td>
                <td style="padding:.5rem .4rem;vertical-align:middle;color:#475569;font-size:.79rem">
                    {{ Str::limit($scan->creator?->name ?? '—', 18) }}
                </td>
                <td class="text-end" style="padding:.5rem .4rem;vertical-align:middle">
                    @if($scan->isCompleted())
                    <span style="font-weight:700;color:#0f172a">{{ number_format($scan->finding_count) }}</span>
                    <div style="font-size:.68rem;color:#94a3b8">{{ $scan->host_count }} host{{ $scan->host_count !== 1 ? 's' : '' }}</div>
                    @else — @endif
                </td>
                <td class="text-end" style="padding:.5rem .4rem;vertical-align:middle">
                    @if($scan->isCompleted())
                    <span style="background:#d1fae5;color:#065f46;padding:.1rem .5rem;border-radius:20px;font-size:.67rem;font-weight:700">Done</span>
                    @elseif($scan->isFailed())
                    <span style="background:#fee2e2;color:#991b1b;padding:.1rem .5rem;border-radius:20px;font-size:.67rem;font-weight:700"
                          title="{{ $scan->upload_error }}">Failed</span>
                    @else
                    <span style="background:#fef3c7;color:#92400e;padding:.1rem .5rem;border-radius:20px;font-size:.67rem;font-weight:700">Processing</span>
                    @endif
                </td>
                <td style="padding:.5rem 0;vertical-align:middle;text-align:right">
                    <form method="POST"
                          action="{{ route('vuln-assessments.scans.destroy', [$assessment, $scan]) }}"
                          onsubmit="return confirm('Delete this verification scan?\n\nThis removes all {{ number_format($scan->finding_count) }} findings and cannot be undone.')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                style="background:#fef2f2;border:1px solid #fecaca;border-radius:7px;color:#dc2626;
                                       padding:.18rem .42rem;font-size:.72rem;cursor:pointer">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
    </div>
    @endif

</div>
</div>

</div>{{-- /row --}}

@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
(function () {
    const CHUNK_SIZE    = 5 * 1024 * 1024;
    const MAX_FILE_SIZE = 250 * 1024 * 1024;
    const POLL_INTERVAL = 2500;
    const POLL_TIMEOUT  = 10 * 60 * 1000;
    const UPLOAD_URL    = '{{ route('vuln-assessments.upload', $assessment) }}';
    const CHUNK_URL     = '{{ route('vuln-assessments.upload.chunk', $assessment) }}';
    const STATUS_BASE   = '{{ url('/vuln-assessments/' . $assessment->uuid . '/scan-status') }}/';
    const REDIRECT      = '{{ route('vuln-assessments.vuln-upload', $assessment) }}';
    const CSRF          = document.querySelector('meta[name="csrf-token"]').content;

    function getCsrf() {
        var m = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
        return m ? decodeURIComponent(m[1]) : CSRF;
    }

    async function refreshCsrf() {
        try { await fetch(window.location.href, { method: 'GET', credentials: 'same-origin' }); } catch (_) {}
    }

    function fmt(bytes) {
        return bytes < 1048576 ? (bytes/1024).toFixed(1)+' KB' : (bytes/1048576).toFixed(1)+' MB';
    }

    function uploadRegular(file, meta, onProgress) {
        return new Promise(function (resolve, reject) {
            var retried = false;
            function attempt() {
                const fd = new FormData();
                fd.append('scan_file',       file);
                fd.append('notes',           meta.remarks);
                fd.append('is_verification', meta.isVerif ? '1' : '0');

                const xhr = new XMLHttpRequest();
                xhr.open('POST', UPLOAD_URL);
                xhr.setRequestHeader('X-CSRF-TOKEN', getCsrf());
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.upload.addEventListener('progress', function (e) {
                    if (e.lengthComputable) onProgress(Math.round((e.loaded / e.total) * 85));
                });
                xhr.onload = async function () {
                    const res = (() => { try { return JSON.parse(xhr.responseText); } catch (_) { return {}; } })();
                    if (xhr.status === 200) {
                        resolve({ scanId: res.scan_id });
                    } else if (xhr.status === 419 && !retried) {
                        retried = true;
                        await refreshCsrf();
                        attempt();
                    } else if (xhr.status === 419) {
                        reject('Session expired — please refresh the page and try again.');
                    } else if (xhr.status === 422) {
                        const msg = res.errors?.scan_file?.[0] || res.errors?.scan_file || res.message || '';
                        if (String(msg).includes('already been uploaded')) resolve({ skipped: true });
                        else reject(msg || 'Validation failed.');
                    } else {
                        reject(res.errors?.scan_file?.[0] || res.message || 'Upload failed (HTTP '+xhr.status+').');
                    }
                };
                xhr.onerror = function () { reject('Network error — check connection and try again.'); };
                xhr.send(fd);
            }
            attempt();
        });
    }

    async function uploadChunked(file, meta, onProgress) {
        const total    = Math.ceil(file.size / CHUNK_SIZE);
        const uploadId = crypto.randomUUID ? crypto.randomUUID()
            : ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c =>
                (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c/4).toString(16));

        for (let i = 0; i < total; i++) {
            const fd = new FormData();
            fd.append('upload_id',       uploadId);
            fd.append('chunk_index',     i);
            fd.append('total_chunks',    total);
            fd.append('filename',        file.name);
            fd.append('notes',           meta.remarks);
            fd.append('is_verification', meta.isVerif ? '1' : '0');
            fd.append('chunk',           file.slice(i*CHUNK_SIZE, (i+1)*CHUNK_SIZE), file.name);

            onProgress(Math.round(((i+0.5)/total)*85));

            var resp = await fetch(CHUNK_URL, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
                credentials: 'same-origin',
                body: fd,
            });
            if (resp.status === 419) {
                await refreshCsrf();
                resp = await fetch(CHUNK_URL, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
                    credentials: 'same-origin',
                    body: fd,
                });
            }

            let data = {};
            try { data = await resp.json(); } catch (_) {}

            if (resp.status === 419) throw 'Session expired — please refresh the page and try again.';
            if (resp.status === 422) {
                const msg = data.errors?.filename?.[0] || data.errors?.chunk?.[0] || data.message || '';
                if (String(msg).includes('already been uploaded')) return { skipped: true };
                throw (msg || 'Chunk '+(i+1)+'/'+total+' rejected.');
            }
            if (!resp.ok) throw (data.message || 'Chunk '+(i+1)+'/'+total+' failed (HTTP '+resp.status+').');
            if (data.status === 'queued') return { scanId: data.scan_id };
        }
        throw 'All chunks sent but no confirmation received. Please retry.';
    }

    function pollScan(scanId, deadline) {
        return new Promise(function (resolve) {
            const tid = setInterval(async function () {
                if (Date.now() > deadline) { clearInterval(tid); resolve('timeout'); return; }
                try {
                    const resp = await fetch(STATUS_BASE + scanId, {
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    });
                    const data = await resp.json();
                    if (data.status === 'completed' || data.status === 'failed') {
                        clearInterval(tid); resolve(data.status);
                    }
                } catch (_) {}
            }, POLL_INTERVAL);
        });
    }

    function makeCard(cfg) {
        const fileInput    = document.getElementById(cfg.fileInput);
        const remarksInput = document.getElementById(cfg.remarksInput);
        const fileNameEl   = document.getElementById(cfg.fileNameEl);
        const dropzone     = document.getElementById(cfg.dropzone);
        const progressWrap = document.getElementById(cfg.progressWrap);
        const progressBar  = document.getElementById(cfg.progressBar);
        const progressLbl  = document.getElementById(cfg.progressLabel);
        const progressPct  = document.getElementById(cfg.progressPct);
        const alertEl      = document.getElementById(cfg.alertEl);
        const btn          = document.getElementById(cfg.btn);

        if (!fileInput || !btn) return;

        dropzone.addEventListener('dragover', function(e) {
            e.preventDefault();
            dropzone.style.borderColor = cfg.accentColor;
            dropzone.style.background  = cfg.hoverBg;
        });
        dropzone.addEventListener('dragleave', function() {
            dropzone.style.borderColor = '';
            dropzone.style.background  = '';
        });
        dropzone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropzone.style.borderColor = '';
            dropzone.style.background  = '';
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });

        fileInput.addEventListener('change', function () {
            const f = fileInput.files[0];
            if (!f) { fileNameEl.textContent = 'No file selected'; return; }
            if (f.size > MAX_FILE_SIZE) {
                showAlert('danger', '"' + f.name + '" exceeds the 250 MB limit.');
                fileInput.value = '';
                return;
            }
            fileNameEl.textContent = f.name + ' (' + fmt(f.size) + ')';
            fileNameEl.style.color = cfg.accentColor;
            hideAlert();
        });

        function setProgress(pct, label) {
            progressWrap.style.display = 'block';
            progressBar.style.width    = pct + '%';
            progressLbl.textContent    = label || 'Uploading…';
            progressPct.textContent    = pct + '%';
        }

        function showAlert(type, msg) {
            alertEl.style.display    = 'block';
            alertEl.style.background = type === 'danger' ? '#fef2f2' : '#f0fdf4';
            alertEl.style.border     = '1px solid ' + (type === 'danger' ? '#fecaca' : '#bbf7d0');
            alertEl.style.color      = type === 'danger' ? '#991b1b' : '#065f46';
            alertEl.innerHTML        = (type === 'danger'
                ? '<i class="bi bi-x-circle-fill me-1"></i>'
                : '<i class="bi bi-check-circle-fill me-1"></i>') + msg;
        }

        function hideAlert() { alertEl.style.display = 'none'; }

        btn.addEventListener('click', async function () {
            const file = fileInput.files[0];
            if (!file) { showAlert('danger', 'Please select a file first.'); return; }

            const meta = {
                remarks: remarksInput ? remarksInput.value.trim() : '',
                isVerif: cfg.isVerif,
            };

            btn.disabled  = true;
            btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Uploading…';
            hideAlert();
            setProgress(0, 'Uploading…');

            try {
                const result = file.size > CHUNK_SIZE
                    ? await uploadChunked(file, meta, function(pct) { setProgress(pct, 'Uploading… ' + pct + '%'); })
                    : await uploadRegular(file,  meta, function(pct) { setProgress(pct, 'Uploading… ' + pct + '%'); });

                if (result.skipped) {
                    setProgress(100, 'Already uploaded');
                    showAlert('danger', 'This file was already uploaded to this assessment.');
                    btn.disabled  = false;
                    btn.innerHTML = cfg.btnLabel;
                    return;
                }

                setProgress(90, 'Processing…');
                btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Processing…';

                const status = await pollScan(result.scanId, Date.now() + POLL_TIMEOUT);
                if (status === 'completed') {
                    setProgress(100, 'Done!');
                    showAlert('success', 'Scan imported successfully. Refreshing…');
                    setTimeout(function () { window.location.href = REDIRECT; }, 900);
                } else if (status === 'timeout') {
                    setProgress(100, 'Queued');
                    showAlert('success', 'Upload received — still processing. Refresh shortly.');
                    btn.disabled  = false;
                    btn.innerHTML = cfg.btnLabel;
                } else {
                    throw 'Server-side processing failed. Check the scan list for error details.';
                }
            } catch (err) {
                progressWrap.style.display = 'none';
                showAlert('danger', String(err));
                btn.disabled  = false;
                btn.innerHTML = cfg.btnLabel;
            }
        });
    }

    makeCard({
        fileInput:    'init-file-input',
        remarksInput: 'init-remarks',
        fileNameEl:   'init-file-name',
        dropzone:     'init-dropzone',
        progressWrap: 'init-progress-wrap',
        progressBar:  'init-progress-bar',
        progressLabel:'init-progress-label',
        progressPct:  'init-progress-pct',
        alertEl:      'init-alert',
        btn:          'init-upload-btn',
        isVerif:      false,
        accentColor:  '#3b82f6',
        hoverBg:      '#dbeafe',
        btnLabel:     '<i class="bi bi-cloud-upload me-1"></i>Import Initial Scan',
    });

    makeCard({
        fileInput:    'verif-file-input',
        remarksInput: 'verif-remarks',
        fileNameEl:   'verif-file-name',
        dropzone:     'verif-dropzone',
        progressWrap: 'verif-progress-wrap',
        progressBar:  'verif-progress-bar',
        progressLabel:'verif-progress-label',
        progressPct:  'verif-progress-pct',
        alertEl:      'verif-alert',
        btn:          'verif-upload-btn',
        isVerif:      true,
        accentColor:  '#16a34a',
        hoverBg:      '#dcfce7',
        btnLabel:     '<i class="bi bi-patch-check me-1"></i>Import Verification Scan',
    });

})();
</script>
@endpush
