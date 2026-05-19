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

    .fq-table { width:100%; border-collapse:collapse; }
    .fq-table th { font-size:.64rem; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.45px; padding:.3rem .5rem; border-bottom:2px solid #f1f5f9; }
    .fq-table td { font-size:.78rem; vertical-align:middle; padding:.38rem .5rem; border-bottom:1px solid #f8fafc; }
    .fq-table tbody tr:last-child td { border-bottom:none; }

    .ua-btn { border:none; border-radius:6px; padding:.2rem .48rem; font-size:.71rem; cursor:pointer; font-weight:600; line-height:1.4; transition:opacity .15s; }
    .ua-btn:hover { opacity:.78; }
    .ua-btn:disabled { opacity:.38; cursor:not-allowed; }

    @keyframes uaspin { to { transform:rotate(360deg); } }
    .spin { display:inline-block; animation:uaspin .75s linear infinite; }
</style>

{{-- ── Page header ── --}}
<div style="background:linear-gradient(135deg,#f8fafc 0%,#eff6ff 100%);
            border:1px solid #bfdbfe;border-radius:14px;padding:1.25rem 1.75rem;margin-bottom:1.25rem">
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
    <div class="col-6 col-sm-4 col-md">
        <div class="stat-strip" style="border-color:#fdba74;background:#fff7ed">
            <div class="lbl" style="color:#9a3412">Persistent</div>
            <div class="val" style="color:#c2410c">{{ number_format($trackingStats->persistent) }}</div>
        </div>
    </div>
</div>
@endif

{{-- ── Two upload cards ── --}}
<div class="row g-3">

{{-- ════════════════════════════════════════════════════════════
     Initial Scan Card
════════════════════════════════════════════════════════════ --}}
<div class="col-12 col-xl-6">
<div class="va-card" style="border-color:#bfdbfe">

    {{-- Card header --}}
    <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.1rem;
                padding-bottom:.9rem;border-bottom:2px solid #dbeafe">
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

    {{-- Drop zone --}}
    <label id="init-dropzone" for="init-file-input"
           style="display:block;border:2px dashed #93c5fd;border-radius:10px;
                  padding:1.4rem 1rem 1.1rem;text-align:center;cursor:pointer;
                  background:#f0f7ff;transition:background .15s,border-color .15s;
                  user-select:none">
        <i class="bi bi-cloud-arrow-up"
           style="font-size:2rem;color:#3b82f6;display:block;margin-bottom:.4rem"></i>
        <div style="font-size:.85rem;font-weight:700;color:#1e40af;margin-bottom:.5rem">
            Drag &amp; drop files here
        </div>
        <span style="display:inline-flex;align-items:center;gap:.4rem;
                     background:#2563eb;color:#fff;border:none;border-radius:8px;
                     padding:.38rem .9rem;font-size:.8rem;font-weight:600;cursor:pointer;
                     box-shadow:0 1px 4px rgba(37,99,235,.3)">
            <i class="bi bi-folder2-open"></i> Browse Files…
        </span>
        <div style="font-size:.7rem;color:#94a3b8;margin-top:.5rem">
            Multiple files supported · .nessus &nbsp;.xml &nbsp;.csv · max 1 GB each
        </div>
        <input type="file" id="init-file-input" accept=".xml,.nessus,.csv" multiple
               style="position:absolute;width:1px;height:1px;opacity:0;pointer-events:none">
    </label>

    {{-- File queue --}}
    <div id="init-file-list" style="display:none;margin-top:.7rem">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.4rem">
            <span style="font-size:.7rem;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.45px">
                Queue
                <span id="init-queue-count"
                      style="background:#dbeafe;color:#1d4ed8;padding:.05rem .48rem;border-radius:20px;
                             font-size:.68rem;font-weight:700;margin-left:.2rem">0</span>
            </span>
            <button id="init-clear-btn"
                    style="display:none;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;
                           color:#15803d;font-size:.71rem;font-weight:600;padding:.2rem .6rem;cursor:pointer">
                <i class="bi bi-check2-all me-1"></i>Clear Completed
            </button>
        </div>
        <div style="border:1px solid #e2e8f0;border-radius:9px;overflow:hidden">
            <div style="max-height:270px;overflow-y:auto">
                <table class="fq-table">
                    <thead style="background:#f8fafc">
                    <tr>
                        <th>File</th>
                        <th>Size</th>
                        <th>Type</th>
                        <th style="min-width:95px">Progress</th>
                        <th>Status</th>
                        <th style="width:62px"></th>
                    </tr>
                    </thead>
                    <tbody id="init-file-tbody"></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Remarks --}}
    <div style="margin-top:.65rem">
        <label style="font-size:.77rem;font-weight:600;color:#374151;display:block;margin-bottom:.3rem">Remarks</label>
        <input type="text" id="init-remarks" class="form-control form-control-sm"
               placeholder="Optional notes about this scan"
               style="border-radius:8px;border-color:#bfdbfe;font-size:.83rem">
    </div>

    {{-- Action row --}}
    <div style="margin-top:.7rem;display:flex;align-items:center;gap:.6rem;flex-wrap:wrap">
        <button id="init-upload-btn" class="btn btn-sm"
                style="background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border:none;
                       border-radius:9px;font-weight:600;padding:.45rem 1.1rem;font-size:.84rem;
                       box-shadow:0 2px 8px rgba(37,99,235,.25)">
            <i class="bi bi-cloud-upload me-1"></i>Upload All
        </button>
        <div id="init-summary" style="display:none;font-size:.77rem"></div>
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
                <th style="font-size:.65rem;font-weight:700;color:#64748b;padding:.3rem 0;border:none;text-transform:uppercase;letter-spacing:.4px">File</th>
                <th style="font-size:.65rem;font-weight:700;color:#64748b;padding:.3rem .4rem;border:none;text-transform:uppercase;letter-spacing:.4px">Size</th>
                <th style="font-size:.65rem;font-weight:700;color:#64748b;padding:.3rem .4rem;border:none;text-transform:uppercase;letter-spacing:.4px">Uploaded</th>
                <th style="font-size:.65rem;font-weight:700;color:#64748b;padding:.3rem .4rem;border:none;text-transform:uppercase;letter-spacing:.4px">By</th>
                <th class="text-end" style="font-size:.65rem;font-weight:700;color:#64748b;padding:.3rem .4rem;border:none;text-transform:uppercase;letter-spacing:.4px">Result</th>
                <th style="border:none;padding:.3rem 0"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($initialScans as $scan)
            <tr style="border-bottom:1px solid #f1f5f9">
                <td style="padding:.45rem 0;vertical-align:middle;max-width:160px">
                    <div style="color:#0f172a;font-size:.77rem;font-family:monospace;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"
                         title="{{ $scan->filename }}">{{ Str::limit($scan->filename, 36) }}</div>
                    @if($scan->is_baseline)
                    <span style="background:var(--lime-muted);color:var(--lime-dark);padding:.05rem .38rem;border-radius:20px;font-size:.62rem;font-weight:700">Baseline</span>
                    @endif
                </td>
                <td style="padding:.45rem .4rem;vertical-align:middle;white-space:nowrap">
                    <span style="font-size:.75rem;color:#475569">
                        @if($scan->file_size)
                            @php
                                $fs = $scan->file_size;
                                echo $fs >= 1073741824 ? number_format($fs/1073741824,2).' GB'
                                   : ($fs >= 1048576 ? number_format($fs/1048576,1).' MB'
                                   : number_format($fs/1024,1).' KB');
                            @endphp
                        @else —
                        @endif
                    </span>
                </td>
                <td style="padding:.45rem .4rem;vertical-align:middle;white-space:nowrap">
                    <span style="font-size:.74rem;color:#475569">{{ $scan->created_at->format('d M Y') }}</span>
                    <div style="font-size:.67rem;color:#94a3b8">{{ $scan->created_at->format('H:i') }}</div>
                </td>
                <td style="padding:.45rem .4rem;vertical-align:middle;color:#475569;font-size:.76rem;white-space:nowrap">
                    {{ Str::limit($scan->creator?->name ?? '—', 16) }}
                </td>
                <td class="text-end" style="padding:.45rem .4rem;vertical-align:middle">
                    @if($scan->isCompleted())
                    <span style="background:#d1fae5;color:#065f46;padding:.1rem .5rem;border-radius:20px;font-size:.66rem;font-weight:700">
                        <i class="bi bi-check-circle-fill me-1"></i>{{ number_format($scan->finding_count) }} findings
                    </span>
                    <div style="font-size:.67rem;color:#94a3b8;margin-top:.1rem">{{ $scan->host_count }} host{{ $scan->host_count !== 1 ? 's' : '' }}</div>
                    @elseif($scan->isFailed())
                    <span style="background:#fee2e2;color:#991b1b;padding:.1rem .5rem;border-radius:20px;font-size:.66rem;font-weight:700"
                          title="{{ $scan->upload_error }}">
                        <i class="bi bi-x-circle-fill me-1"></i>Failed
                    </span>
                    @if($scan->upload_error)
                    <div style="font-size:.67rem;color:#ef4444;margin-top:.1rem;max-width:140px;white-space:normal">{{ Str::limit($scan->upload_error, 60) }}</div>
                    @endif
                    @else
                    <span style="background:#fef3c7;color:#92400e;padding:.1rem .5rem;border-radius:20px;font-size:.66rem;font-weight:700">
                        <i class="bi bi-gear-fill me-1"></i>Processing
                    </span>
                    @endif
                </td>
                <td style="padding:.45rem 0;vertical-align:middle;text-align:right">
                    <div style="display:inline-flex;gap:.3rem;align-items:center">
                        @if($scan->file_path)
                        <a href="{{ route('vuln-assessments.scans.download', [$assessment, $scan]) }}"
                           style="display:inline-flex;align-items:center;background:#eff6ff;border:1px solid #bfdbfe;
                                  border-radius:7px;color:#1d4ed8;padding:.18rem .42rem;font-size:.72rem;text-decoration:none"
                           title="Download {{ $scan->filename }}">
                            <i class="bi bi-download"></i>
                        </a>
                        @endif
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
                    </div>
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

{{-- ════════════════════════════════════════════════════════════
     Verification Scan Card
════════════════════════════════════════════════════════════ --}}
<div class="col-12 col-xl-6">
<div class="va-card" style="border-color:#bbf7d0">

    {{-- Card header --}}
    <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.1rem;
                padding-bottom:.9rem;border-bottom:2px solid #bbf7d0">
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

    {{-- Drop zone --}}
    <label id="verif-dropzone" for="verif-file-input"
           style="display:block;border:2px dashed #4ade80;border-radius:10px;
                  padding:1.4rem 1rem 1.1rem;text-align:center;cursor:pointer;
                  background:#f0fdf4;transition:background .15s,border-color .15s;
                  user-select:none">
        <i class="bi bi-cloud-arrow-up"
           style="font-size:2rem;color:#16a34a;display:block;margin-bottom:.4rem"></i>
        <div style="font-size:.85rem;font-weight:700;color:#065f46;margin-bottom:.5rem">
            Drag &amp; drop files here
        </div>
        <span style="display:inline-flex;align-items:center;gap:.4rem;
                     background:#16a34a;color:#fff;border:none;border-radius:8px;
                     padding:.38rem .9rem;font-size:.8rem;font-weight:600;cursor:pointer;
                     box-shadow:0 1px 4px rgba(22,163,74,.3)">
            <i class="bi bi-folder2-open"></i> Browse Files…
        </span>
        <div style="font-size:.7rem;color:#94a3b8;margin-top:.5rem">
            Multiple files supported · .nessus &nbsp;.xml &nbsp;.csv · max 1 GB each
        </div>
        <input type="file" id="verif-file-input" accept=".xml,.nessus,.csv" multiple
               style="position:absolute;width:1px;height:1px;opacity:0;pointer-events:none">
    </label>

    {{-- File queue --}}
    <div id="verif-file-list" style="display:none;margin-top:.7rem">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.4rem">
            <span style="font-size:.7rem;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.45px">
                Queue
                <span id="verif-queue-count"
                      style="background:#d1fae5;color:#059669;padding:.05rem .48rem;border-radius:20px;
                             font-size:.68rem;font-weight:700;margin-left:.2rem">0</span>
            </span>
            <button id="verif-clear-btn"
                    style="display:none;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;
                           color:#15803d;font-size:.71rem;font-weight:600;padding:.2rem .6rem;cursor:pointer">
                <i class="bi bi-check2-all me-1"></i>Clear Completed
            </button>
        </div>
        <div style="border:1px solid #e2e8f0;border-radius:9px;overflow:hidden">
            <div style="max-height:270px;overflow-y:auto">
                <table class="fq-table">
                    <thead style="background:#f8fafc">
                    <tr>
                        <th>File</th>
                        <th>Size</th>
                        <th>Type</th>
                        <th style="min-width:95px">Progress</th>
                        <th>Status</th>
                        <th style="width:62px"></th>
                    </tr>
                    </thead>
                    <tbody id="verif-file-tbody"></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Remarks --}}
    <div style="margin-top:.65rem">
        <label style="font-size:.77rem;font-weight:600;color:#374151;display:block;margin-bottom:.3rem">Verification Remarks</label>
        <input type="text" id="verif-remarks" class="form-control form-control-sm"
               placeholder="Optional notes about this verification"
               style="border-radius:8px;border-color:#86efac;font-size:.83rem">
    </div>

    {{-- Action row --}}
    <div style="margin-top:.7rem;display:flex;align-items:center;gap:.6rem;flex-wrap:wrap">
        <button id="verif-upload-btn" class="btn btn-sm"
                style="background:linear-gradient(135deg,#16a34a,#059669);color:#fff;border:none;
                       border-radius:9px;font-weight:600;padding:.45rem 1.1rem;font-size:.84rem;
                       box-shadow:0 2px 8px rgba(22,163,74,.25)">
            <i class="bi bi-patch-check me-1"></i>Upload All
        </button>
        <div id="verif-summary" style="display:none;font-size:.77rem"></div>
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
                <th style="font-size:.65rem;font-weight:700;color:#64748b;padding:.3rem 0;border:none;text-transform:uppercase;letter-spacing:.4px">File</th>
                <th style="font-size:.65rem;font-weight:700;color:#64748b;padding:.3rem .4rem;border:none;text-transform:uppercase;letter-spacing:.4px">Size</th>
                <th style="font-size:.65rem;font-weight:700;color:#64748b;padding:.3rem .4rem;border:none;text-transform:uppercase;letter-spacing:.4px">Uploaded</th>
                <th style="font-size:.65rem;font-weight:700;color:#64748b;padding:.3rem .4rem;border:none;text-transform:uppercase;letter-spacing:.4px">By</th>
                <th class="text-end" style="font-size:.65rem;font-weight:700;color:#64748b;padding:.3rem .4rem;border:none;text-transform:uppercase;letter-spacing:.4px">Result</th>
                <th style="border:none;padding:.3rem 0"></th>
            </tr>
            </thead>
            <tbody>
            @foreach($verificationScans as $scan)
            <tr style="border-bottom:1px solid #f1f5f9">
                <td style="padding:.45rem 0;vertical-align:middle;max-width:160px">
                    <div style="color:#0f172a;font-size:.77rem;font-family:monospace;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"
                         title="{{ $scan->filename }}">{{ Str::limit($scan->filename, 36) }}</div>
                </td>
                <td style="padding:.45rem .4rem;vertical-align:middle;white-space:nowrap">
                    <span style="font-size:.75rem;color:#475569">
                        @if($scan->file_size)
                            @php
                                $fs = $scan->file_size;
                                echo $fs >= 1073741824 ? number_format($fs/1073741824,2).' GB'
                                   : ($fs >= 1048576 ? number_format($fs/1048576,1).' MB'
                                   : number_format($fs/1024,1).' KB');
                            @endphp
                        @else —
                        @endif
                    </span>
                </td>
                <td style="padding:.45rem .4rem;vertical-align:middle;white-space:nowrap">
                    <span style="font-size:.74rem;color:#475569">{{ $scan->created_at->format('d M Y') }}</span>
                    <div style="font-size:.67rem;color:#94a3b8">{{ $scan->created_at->format('H:i') }}</div>
                </td>
                <td style="padding:.45rem .4rem;vertical-align:middle;color:#475569;font-size:.76rem;white-space:nowrap">
                    {{ Str::limit($scan->creator?->name ?? '—', 16) }}
                </td>
                <td class="text-end" style="padding:.45rem .4rem;vertical-align:middle">
                    @if($scan->isCompleted())
                    <span style="background:#d1fae5;color:#065f46;padding:.1rem .5rem;border-radius:20px;font-size:.66rem;font-weight:700">
                        <i class="bi bi-check-circle-fill me-1"></i>{{ number_format($scan->finding_count) }} findings
                    </span>
                    <div style="font-size:.67rem;color:#94a3b8;margin-top:.1rem">{{ $scan->host_count }} host{{ $scan->host_count !== 1 ? 's' : '' }}</div>
                    @elseif($scan->isFailed())
                    <span style="background:#fee2e2;color:#991b1b;padding:.1rem .5rem;border-radius:20px;font-size:.66rem;font-weight:700"
                          title="{{ $scan->upload_error }}">
                        <i class="bi bi-x-circle-fill me-1"></i>Failed
                    </span>
                    @if($scan->upload_error)
                    <div style="font-size:.67rem;color:#ef4444;margin-top:.1rem;max-width:140px;white-space:normal">{{ Str::limit($scan->upload_error, 60) }}</div>
                    @endif
                    @else
                    <span style="background:#fef3c7;color:#92400e;padding:.1rem .5rem;border-radius:20px;font-size:.66rem;font-weight:700">
                        <i class="bi bi-gear-fill me-1"></i>Processing
                    </span>
                    @endif
                </td>
                <td style="padding:.45rem 0;vertical-align:middle;text-align:right">
                    <div style="display:inline-flex;gap:.3rem;align-items:center">
                        @if($scan->file_path)
                        <a href="{{ route('vuln-assessments.scans.download', [$assessment, $scan]) }}"
                           style="display:inline-flex;align-items:center;background:#eff6ff;border:1px solid #bfdbfe;
                                  border-radius:7px;color:#1d4ed8;padding:.18rem .42rem;font-size:.72rem;text-decoration:none"
                           title="Download {{ $scan->filename }}">
                            <i class="bi bi-download"></i>
                        </a>
                        @endif
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
                    </div>
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
    'use strict';

    const CHUNK_SIZE    = 5 * 1024 * 1024;
    const MAX_FILE_SIZE = 1024 * 1024 * 1024;
    const POLL_INTERVAL = 2500;
    const POLL_TIMEOUT  = 10 * 60 * 1000;
    const UPLOAD_URL    = '{{ route('vuln-assessments.upload', $assessment) }}';
    const CHUNK_URL     = '{{ route('vuln-assessments.upload.chunk', $assessment) }}';
    const STATUS_BASE   = '{{ url('/vuln-assessments/' . $assessment->uuid . '/scan-status') }}/';
    const REDIRECT      = '{{ route('vuln-assessments.vuln-upload', $assessment) }}';

    let csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    function getCsrf() { return csrfToken; }

    async function refreshCsrf() {
        try {
            const resp = await fetch(window.location.href, { credentials: 'same-origin' });
            const html = await resp.text();
            const m = html.match(/<meta[^>]+name="csrf-token"[^>]+content="([^"]+)"/i)
                   || html.match(/<meta[^>]+content="([^"]+)"[^>]+name="csrf-token"/i);
            if (m) csrfToken = m[1];
        } catch (_) {}
    }

    function fmt(bytes) {
        if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
        if (bytes >= 1048576)    return (bytes / 1048576).toFixed(1) + ' MB';
        return (bytes / 1024).toFixed(1) + ' KB';
    }

    // ── Regular upload (files < CHUNK_SIZE) ──────────────────────────────────
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
                    } else if ((xhr.status === 419 || xhr.status === 401) && !retried) {
                        retried = true;
                        await refreshCsrf();
                        attempt();
                    } else if (xhr.status === 419 || xhr.status === 401) {
                        reject(`Session expired. Please <a href="{{ route('login') }}" style="color:inherit;font-weight:700">log in again</a> and retry.`);
                    } else if (xhr.status === 422) {
                        const sf  = res.errors?.scan_file;
                        const msg = (Array.isArray(sf) ? sf[0] : sf) || res.message || '';
                        if (String(msg).includes('already been uploaded')) resolve({ skipped: true });
                        else reject(msg || 'Validation failed.');
                    } else {
                        const sf  = res.errors?.scan_file;
                        reject((Array.isArray(sf) ? sf[0] : sf) || res.message || 'Upload failed (HTTP ' + xhr.status + ').');
                    }
                };
                xhr.onerror = function () { reject('Network error — check connection and try again.'); };
                xhr.send(fd);
            }
            attempt();
        });
    }

    // ── Chunked upload (files ≥ CHUNK_SIZE) ──────────────────────────────────
    async function uploadChunked(file, meta, onProgress) {
        const total    = Math.ceil(file.size / CHUNK_SIZE);
        const uploadId = crypto.randomUUID
            ? crypto.randomUUID()
            : ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, function(c) {
                return (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16);
            });

        for (let i = 0; i < total; i++) {
            const fd = new FormData();
            fd.append('upload_id',       uploadId);
            fd.append('chunk_index',     i);
            fd.append('total_chunks',    total);
            fd.append('filename',        file.name);
            fd.append('notes',           meta.remarks);
            fd.append('is_verification', meta.isVerif ? '1' : '0');
            fd.append('chunk',           file.slice(i * CHUNK_SIZE, (i + 1) * CHUNK_SIZE), file.name);

            onProgress(Math.round(((i + 0.5) / total) * 85));

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

            if (resp.status === 419 || resp.status === 401) {
                throw `Session expired. Please <a href="{{ route('login') }}" style="color:inherit;font-weight:700">log in again</a> and retry.`;
            }
            if (resp.status === 422) {
                const msg = data.errors?.filename?.[0] || data.errors?.chunk?.[0] || data.message || '';
                if (String(msg).includes('already been uploaded')) return { skipped: true };
                throw (msg || 'Chunk ' + (i + 1) + '/' + total + ' rejected.');
            }
            if (!resp.ok) throw (data.message || 'Chunk ' + (i + 1) + '/' + total + ' failed (HTTP ' + resp.status + ').');
            if (data.status === 'queued') return { scanId: data.scan_id };
        }
        throw 'All chunks sent but no confirmation received. Please retry.';
    }

    // ── Poll scan status ──────────────────────────────────────────────────────
    function pollScan(scanId, deadline) {
        return new Promise(function (resolve) {
            const tid = setInterval(async function () {
                if (Date.now() > deadline) { clearInterval(tid); resolve('timeout'); return; }
                try {
                    const resp = await fetch(STATUS_BASE + scanId, {
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
                    });
                    const data = await resp.json();
                    if (data.status === 'completed' || data.status === 'failed') {
                        clearInterval(tid); resolve(data.status);
                    }
                } catch (_) {}
            }, POLL_INTERVAL);
        });
    }

    // ── Status config ─────────────────────────────────────────────────────────
    const STATUS_CFG = {
        pending:    { bg:'#f8fafc', color:'#64748b', border:'#e2e8f0', icon:'bi-hourglass',            label:'Pending'    },
        uploading:  { bg:'#eff6ff', color:'#1d4ed8', border:'#bfdbfe', icon:'bi-arrow-up-circle-fill', label:'Uploading'  },
        processing: { bg:'#fefce8', color:'#854d0e', border:'#fde68a', icon:'bi-gear-fill',            label:'Processing' },
        completed:  { bg:'#f0fdf4', color:'#15803d', border:'#bbf7d0', icon:'bi-check-circle-fill',    label:'Completed'  },
        failed:     { bg:'#fff1f2', color:'#be123c', border:'#fecdd3', icon:'bi-x-circle-fill',        label:'Failed'     },
        skipped:    { bg:'#f0fdfa', color:'#0f766e', border:'#99f6e4', icon:'bi-skip-forward-fill',    label:'Skipped'    },
    };

    // ── makeCard ──────────────────────────────────────────────────────────────
    function makeCard(cfg) {
        var fileStates  = [];
        var isUploading = false;

        var fileInput  = document.getElementById(cfg.fileInput);
        var dropzone   = document.getElementById(cfg.dropzone);
        var fileList   = document.getElementById(cfg.fileList);
        var queueCount = document.getElementById(cfg.queueCount);
        var fileTbody  = document.getElementById(cfg.fileTbody);
        var remarks    = document.getElementById(cfg.remarks);
        var uploadBtn  = document.getElementById(cfg.uploadBtn);
        var clearBtn   = document.getElementById(cfg.clearBtn);
        var summaryEl  = document.getElementById(cfg.summary);

        if (!fileInput || !uploadBtn) return;

        function genId() {
            return crypto.randomUUID
                ? crypto.randomUUID()
                : Date.now().toString(36) + Math.random().toString(36).slice(2);
        }

        function typeBadge(filename) {
            var ext = filename.split('.').pop().toLowerCase();
            var map = { nessus:['#dbeafe','#1d4ed8'], xml:['#e0f2fe','#0369a1'], csv:['#dcfce7','#15803d'] };
            var c   = map[ext] || ['#f1f5f9','#64748b'];
            return '<span style="background:'+c[0]+';color:'+c[1]+';padding:.1rem .45rem;border-radius:4px;'
                 + 'font-size:.63rem;font-weight:700;text-transform:uppercase">'+ext+'</span>';
        }

        function statusBadge(status) {
            var sc = STATUS_CFG[status] || STATUS_CFG.pending;
            var spinning = (status === 'uploading' || status === 'processing');
            var iconCls  = 'bi ' + sc.icon + (spinning ? ' spin' : '');
            return '<span style="background:'+sc.bg+';color:'+sc.color+';border:1px solid '+sc.border+';'
                 + 'padding:.15rem .55rem;border-radius:20px;font-size:.66rem;font-weight:700;'
                 + 'white-space:nowrap;display:inline-flex;align-items:center;gap:.25rem">'
                 + '<i class="'+iconCls+'"></i>'+sc.label+'</span>';
        }

        function actionCell(state) {
            if (state.status === 'pending') {
                return '<button class="ua-btn" data-remove="'+state.id+'" title="Remove" '
                     + 'style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626">'
                     + '<i class="bi bi-x-lg"></i></button>';
            }
            if (state.status === 'failed') {
                return '<button class="ua-btn" data-retry="'+state.id+'" title="Retry" '
                     + 'style="background:#eff6ff;border:1px solid #bfdbfe;color:#2563eb">'
                     + '<i class="bi bi-arrow-clockwise me-1"></i>Retry</button>';
            }
            return '';
        }

        function barColor(status) {
            if (status === 'completed') return '#22c55e';
            if (status === 'failed')    return '#ef4444';
            if (status === 'skipped')   return '#2dd4bf';
            return cfg.accentColor;
        }

        function buildRow(state) {
            var name = state.file.name.length > 34
                ? state.file.name.slice(0, 32) + '…'
                : state.file.name;
            var pct  = state.progress || 0;
            var bc   = barColor(state.status);

            return '<tr id="row-' + state.id + '">'
                + '<td style="padding:.38rem .5rem;max-width:170px">'
                +   '<div style="font-weight:600;color:#0f172a;font-family:monospace;font-size:.76rem;'
                +        'white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="' + state.file.name + '">' + name + '</div>'
                +   '<div data-msg="'+state.id+'" style="font-size:.68rem;color:#64748b;margin-top:.1rem">' + (state.message || '') + '</div>'
                + '</td>'
                + '<td style="padding:.38rem .5rem;white-space:nowrap">'
                +   '<span style="font-size:.73rem;color:#475569">' + fmt(state.file.size) + '</span>'
                + '</td>'
                + '<td style="padding:.38rem .5rem">' + typeBadge(state.file.name) + '</td>'
                + '<td style="padding:.38rem .5rem">'
                +   '<div style="height:5px;border-radius:4px;background:#e2e8f0;overflow:hidden;min-width:80px">'
                +     '<div data-bar="'+state.id+'" style="height:100%;width:'+pct+'%;background:'+bc+';border-radius:4px;transition:width .25s ease"></div>'
                +   '</div>'
                +   '<div data-pct="'+state.id+'" style="font-size:.63rem;color:#94a3b8;text-align:right;margin-top:.1rem">'+pct+'%</div>'
                + '</td>'
                + '<td style="padding:.38rem .5rem" data-status-cell="'+state.id+'">' + statusBadge(state.status) + '</td>'
                + '<td style="padding:.38rem .5rem;text-align:right" data-action-cell="'+state.id+'">' + actionCell(state) + '</td>'
                + '</tr>';
        }

        function addFiles(incoming) {
            Array.from(incoming).forEach(function (file) {
                if (file.size > MAX_FILE_SIZE) return;
                if (fileStates.some(function (s) { return s.file.name === file.name; })) return;
                fileStates.push({ id: genId(), file: file, status: 'pending', progress: 0, message: '', scanId: null });
            });
            renderAll();
            fileInput.value = '';
        }

        function renderAll() {
            if (fileList)   fileList.style.display   = fileStates.length ? 'block' : 'none';
            if (queueCount) queueCount.textContent   = fileStates.length;
            fileTbody.innerHTML = fileStates.map(buildRow).join('');
            refreshButtons();
            refreshSummary();
        }

        function refreshButtons() {
            var pending   = fileStates.filter(function (s) { return s.status === 'pending'; });
            var completed = fileStates.filter(function (s) { return s.status === 'completed' || s.status === 'skipped'; });

            uploadBtn.disabled = pending.length === 0 || isUploading;
            uploadBtn.innerHTML = isUploading
                ? '<i class="bi bi-hourglass-split me-1 spin"></i>Uploading…'
                : '<i class="bi bi-cloud-upload me-1"></i>' + cfg.btnLabel + (pending.length ? ' (' + pending.length + ')' : '');

            if (clearBtn) clearBtn.style.display = completed.length > 0 ? 'inline-block' : 'none';
        }

        function refreshSummary() {
            if (!summaryEl) return;
            if (!fileStates.length) { summaryEl.style.display = 'none'; return; }

            var done    = fileStates.filter(function (s) { return s.status === 'completed'; }).length;
            var failed  = fileStates.filter(function (s) { return s.status === 'failed'; }).length;
            var active  = fileStates.filter(function (s) { return s.status === 'uploading' || s.status === 'processing'; }).length;
            var skipped = fileStates.filter(function (s) { return s.status === 'skipped'; }).length;

            var allDone = fileStates.every(function (s) {
                return s.status === 'completed' || s.status === 'failed' || s.status === 'skipped';
            });

            var parts = [];
            if (done)    parts.push('<span style="color:#15803d;font-weight:700"><i class="bi bi-check-circle-fill me-1"></i>' + done + ' completed</span>');
            if (failed)  parts.push('<span style="color:#be123c;font-weight:700"><i class="bi bi-x-circle-fill me-1"></i>' + failed + ' failed</span>');
            if (active)  parts.push('<span style="color:#854d0e;font-weight:700"><i class="bi bi-gear-fill spin me-1"></i>' + active + ' processing</span>');
            if (skipped) parts.push('<span style="color:#0f766e;font-weight:700"><i class="bi bi-skip-forward-fill me-1"></i>' + skipped + ' skipped</span>');

            if (allDone && done > 0) {
                parts.push('<a href="' + REDIRECT + '" style="color:' + cfg.accentColor + ';font-weight:700;text-decoration:none">'
                         + '<i class="bi bi-arrow-clockwise me-1"></i>Refresh findings</a>');
            }

            if (parts.length) {
                summaryEl.innerHTML = '<span style="color:#94a3b8">' + fileStates.length + ' total&nbsp;·&nbsp;</span>' + parts.join('&nbsp;·&nbsp;');
                summaryEl.style.display = 'block';
            } else {
                summaryEl.style.display = 'none';
            }
        }

        function patchRow(id, status, pct, message) {
            var state = fileStates.find(function (s) { return s.id === id; });
            if (state) {
                state.status   = status;
                if (pct      !== undefined) state.progress = pct;
                if (message  !== undefined) state.message  = message;
            }

            var bar       = document.querySelector('[data-bar="'+id+'"]');
            var pctEl     = document.querySelector('[data-pct="'+id+'"]');
            var statusEl  = document.querySelector('[data-status-cell="'+id+'"]');
            var actionEl  = document.querySelector('[data-action-cell="'+id+'"]');
            var msgEl     = document.querySelector('[data-msg="'+id+'"]');

            if (bar && pct !== undefined) {
                bar.style.width      = pct + '%';
                bar.style.background = barColor(status);
            }
            if (pctEl     && pct     !== undefined) pctEl.textContent    = pct + '%';
            if (statusEl)                           statusEl.innerHTML   = statusBadge(status);
            if (actionEl  && state)                 actionEl.innerHTML   = actionCell(state);
            if (msgEl     && message !== undefined) {
                msgEl.innerHTML  = message;
                msgEl.style.color = status === 'failed' ? '#dc2626' : '#64748b';
            }

            refreshButtons();
            refreshSummary();
        }

        async function processFile(state) {
            var meta = {
                remarks: remarks ? remarks.value.trim() : '',
                isVerif: cfg.isVerif,
            };

            patchRow(state.id, 'uploading', 0, '');

            try {
                var result = state.file.size > CHUNK_SIZE
                    ? await uploadChunked(state.file, meta, function (p) { patchRow(state.id, 'uploading', p); })
                    : await uploadRegular(state.file,  meta, function (p) { patchRow(state.id, 'uploading', p); });

                if (result.skipped) {
                    patchRow(state.id, 'skipped', 100, 'Already uploaded — skipped.');
                    return;
                }

                patchRow(state.id, 'processing', 90, 'Processing scan data…');

                var pollResult = await pollScan(result.scanId, Date.now() + POLL_TIMEOUT);

                if (pollResult === 'completed') {
                    patchRow(state.id, 'completed', 100, 'Imported successfully.');
                } else if (pollResult === 'timeout') {
                    patchRow(state.id, 'processing', 100, 'Still processing — check back shortly.');
                } else {
                    patchRow(state.id, 'failed', 0, 'Server processing failed. Check scan list for details.');
                }
            } catch (err) {
                patchRow(state.id, 'failed', 0, String(err));
            }
        }

        uploadBtn.addEventListener('click', async function () {
            if (isUploading) return;
            isUploading = true;
            refreshButtons();

            var pending = fileStates.filter(function (s) { return s.status === 'pending'; });
            for (var i = 0; i < pending.length; i++) {
                await processFile(pending[i]);
            }

            isUploading = false;
            refreshButtons();
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                fileStates = fileStates.filter(function (s) {
                    return s.status !== 'completed' && s.status !== 'skipped';
                });
                renderAll();
            });
        }

        fileTbody.addEventListener('click', async function (e) {
            var retryBtn  = e.target.closest('[data-retry]');
            var removeBtn = e.target.closest('[data-remove]');

            if (retryBtn && !isUploading) {
                var id    = retryBtn.dataset.retry;
                var state = fileStates.find(function (s) { return s.id === id; });
                if (state) {
                    state.status   = 'pending';
                    state.progress = 0;
                    state.message  = '';
                    isUploading    = true;
                    refreshButtons();
                    await processFile(state);
                    isUploading = false;
                    refreshButtons();
                }
            }

            if (removeBtn && !isUploading) {
                var rid = removeBtn.dataset.remove;
                fileStates = fileStates.filter(function (s) { return s.id !== rid; });
                renderAll();
            }
        });

        dropzone.addEventListener('dragover', function (e) {
            e.preventDefault();
            dropzone.style.borderColor = cfg.accentColor;
            dropzone.style.background  = cfg.hoverBg;
        });
        dropzone.addEventListener('dragleave', function () {
            dropzone.style.borderColor = '';
            dropzone.style.background  = '';
        });
        dropzone.addEventListener('drop', function (e) {
            e.preventDefault();
            dropzone.style.borderColor = '';
            dropzone.style.background  = '';
            if (e.dataTransfer.files.length) addFiles(e.dataTransfer.files);
        });
        fileInput.addEventListener('change', function () {
            if (fileInput.files.length) addFiles(fileInput.files);
        });
    }

    // ── Init card ─────────────────────────────────────────────────────────────
    makeCard({
        fileInput:   'init-file-input',
        dropzone:    'init-dropzone',
        fileList:    'init-file-list',
        queueCount:  'init-queue-count',
        fileTbody:   'init-file-tbody',
        remarks:     'init-remarks',
        uploadBtn:   'init-upload-btn',
        clearBtn:    'init-clear-btn',
        summary:     'init-summary',
        isVerif:     false,
        accentColor: '#3b82f6',
        hoverBg:     '#dbeafe',
        btnLabel:    'Upload All',
    });

    // ── Verification card ─────────────────────────────────────────────────────
    makeCard({
        fileInput:   'verif-file-input',
        dropzone:    'verif-dropzone',
        fileList:    'verif-file-list',
        queueCount:  'verif-queue-count',
        fileTbody:   'verif-file-tbody',
        remarks:     'verif-remarks',
        uploadBtn:   'verif-upload-btn',
        clearBtn:    'verif-clear-btn',
        summary:     'verif-summary',
        isVerif:     true,
        accentColor: '#16a34a',
        hoverBg:     '#dcfce7',
        btnLabel:    'Upload All',
    });

})();
</script>
@endpush
