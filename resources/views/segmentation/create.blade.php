@extends('layouts.app')

@section('title', 'New Segmentation Test')

@section('content')
<div class="page-header">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('segmentation.index') }}">Segmentation Tests</a></li>
            <li class="breadcrumb-item active">New Test</li>
        </ol>
    </nav>
    <h4><i class="bi bi-diagram-3-fill me-2" style="color:var(--primary)"></i>New Segmentation Test</h4>
    <p>Upload an Nmap scan file to automatically analyse network segmentation between subnets.</p>
</div>

<style>
.form-card {
    background:#fff; border:1px solid var(--border); border-radius:14px;
    padding:1.5rem; margin-bottom:1.25rem;
    box-shadow:0 1px 4px rgba(0,0,0,.04);
}
.form-card h6 {
    font-size:.85rem; font-weight:700; text-transform:uppercase;
    letter-spacing:.5px; color:var(--primary-dark);
    margin-bottom:1rem; padding-bottom:.6rem;
    border-bottom:2px solid color-mix(in srgb,var(--primary) 15%,white);
}
.form-label { font-size:.8rem; font-weight:600; color:#374151; margin-bottom:.35rem; }
.form-control, .form-select {
    font-size:.875rem; border-radius:10px; border-color:var(--border);
}
.form-control:focus, .form-select:focus {
    border-color:var(--primary);
    box-shadow:0 0 0 .2rem rgba(var(--primary-rgb),.15);
}

/* Drop zone */
.drop-zone {
    border:2.5px dashed var(--border); border-radius:14px;
    padding:2.5rem 1.5rem; text-align:center; cursor:pointer;
    transition:all .25s; background:#fafbfc;
}
.drop-zone.dragover {
    border-color:var(--primary); background:color-mix(in srgb,var(--primary) 7%,white);
}
.drop-zone .dz-icon { font-size:2.5rem; color:var(--primary); margin-bottom:.75rem; }
.drop-zone .dz-label { font-weight:600; color:#374151; font-size:.9rem; }
.drop-zone .dz-sub { font-size:.78rem; color:var(--text-muted); margin-top:.3rem; }
.drop-zone.has-file { border-color:#10b981; background:#f0fdf4; }
.drop-zone.has-file .dz-icon { color:#10b981; }

.info-box {
    background:color-mix(in srgb,var(--primary) 9%,white);
    border:1px solid color-mix(in srgb,var(--primary) 25%,white);
    border-radius:12px; padding:1rem 1.25rem;
    font-size:.82rem; color:#374151; line-height:1.6;
}
.info-box strong { color:var(--primary-dark); }
</style>

<form method="POST" action="{{ route('segmentation.store') }}" enctype="multipart/form-data" id="segForm">
@csrf

{{-- Test Details --}}
<div class="form-card">
    <h6><i class="bi bi-info-circle me-2"></i>Test Details</h6>
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label">Test Name <span class="text-danger">*</span></label>
            <input type="text" name="name"
                   class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name') }}"
                   placeholder="e.g. Q2 2026 — Corp LAN vs DMZ Segmentation Check"
                   required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Scanner IP Address <span class="text-danger">*</span></label>
            <input type="text" name="scanner_ip"
                   class="form-control @error('scanner_ip') is-invalid @enderror"
                   value="{{ old('scanner_ip') }}"
                   placeholder="e.g. 10.10.1.100"
                   required>
            @error('scanner_ip')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">IP of the machine that ran Nmap.</div>
        </div>
        <div class="col-12">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror"
                      rows="2" placeholder="Optional — scope, reason, environment details…">{{ old('notes') }}</textarea>
            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

{{-- File Upload --}}
<div class="form-card">
    <h6><i class="bi bi-cloud-upload me-2"></i>Nmap Scan File</h6>

    <div class="drop-zone" id="dropZone">
        <div class="dz-icon"><i class="bi bi-file-earmark-code"></i></div>
        <div class="dz-label" id="dzLabel">Drag & drop your Nmap scan file here</div>
        <div class="dz-sub">or click to browse &bull; .xml, .nmap, .txt &bull; max 200 MB</div>
        <input type="file" name="scan_file" id="scanFile"
               accept=".xml,.nmap,.txt"
               class="@error('scan_file') is-invalid @enderror"
               style="position:absolute;opacity:0;pointer-events:none" required>
    </div>
    @error('scan_file')<div class="text-danger mt-1" style="font-size:.8rem">{{ $message }}</div>@enderror

    <div class="info-box mt-3">
        <strong>Supported formats:</strong>
        <ul class="mb-0 mt-1 ps-3">
            <li><strong>.xml</strong> — Nmap XML output (<code>nmap -oX scan.xml …</code>) — best accuracy</li>
            <li><strong>.nmap</strong> — Nmap default text output (<code>nmap -oN scan.nmap …</code>)</li>
            <li><strong>.txt</strong> — Plain-text or XML Nmap output saved as .txt</li>
        </ul>
        <div class="mt-2">
            <strong>Tip:</strong> For multi-subnet scans run:
            <code>nmap -sV -oX scan.xml 10.10.10.0/24 10.10.11.0/24 10.10.12.0/24</code>
        </div>
    </div>
</div>

{{-- Submit --}}
<div class="d-flex gap-2 justify-content-end">
    <a href="{{ route('segmentation.index') }}" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn px-4" style="background:var(--primary);color:#fff" id="submitBtn">
        <i class="bi bi-play-fill me-1"></i>Run Segmentation Test
    </button>
</div>

</form>

@push('scripts')
<script nonce="{{ csp_nonce() }}">
(function () {
    const dropZone  = document.getElementById('dropZone');
    const fileInput = document.getElementById('scanFile');
    const dzLabel   = document.getElementById('dzLabel');
    const submitBtn = document.getElementById('submitBtn');
    const form      = document.getElementById('segForm');

    dropZone.addEventListener('click', () => fileInput.click());

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });

    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            setFile(e.dataTransfer.files[0]);
        }
    });

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length) setFile(fileInput.files[0]);
    });

    function setFile(file) {
        // Transfer to the real input if dropped
        const dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;

        const sizeStr = file.size > 1048576
            ? (file.size / 1048576).toFixed(1) + ' MB'
            : Math.round(file.size / 1024) + ' KB';

        dropZone.classList.add('has-file');
        dropZone.querySelector('.dz-icon i').className = 'bi bi-file-earmark-check-fill';
        dzLabel.textContent = file.name + ' (' + sizeStr + ')';
    }

    form.addEventListener('submit', () => {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Uploading…';
    });
})();
</script>
@endpush
@endsection
