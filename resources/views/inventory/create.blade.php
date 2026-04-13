@extends('layouts.app')
@section('title', isset($asset->id) ? 'Edit Asset' : 'Add Asset')

@section('content')
@php $editing = isset($asset->id); @endphp

<div class="page-header">
    <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Servers Asset Inventory</a></li>
        <li class="breadcrumb-item active">{{ $editing ? 'Edit Asset' : 'Add Asset' }}</li>
    </ol></nav>
    <h4><i class="bi bi-{{ $editing ? 'pencil-square' : 'plus-circle-fill' }} me-2" style="color:rgb(152,194,10)"></i>
        {{ $editing ? 'Edit Asset: '.$asset->ip_address : 'Add New Asset' }}
    </h4>
    <p>{{ $editing ? 'Update asset details and classification.' : 'Enter asset details manually or use auto-classify to populate fields.' }}</p>
</div>

<form method="POST"
      action="{{ $editing ? route('inventory.update', $asset) : route('inventory.store') }}"
      id="assetForm">
    @csrf
    @if($editing) @method('PUT') @endif

    <div class="row g-4">

        {{-- Left Column: Asset Details --}}
        <div class="col-lg-8">

            {{-- Scan Data --}}
            <div class="card mb-4">
                <div class="card-header bg-white" style="border-radius:14px 14px 0 0;border-bottom:1px solid #e2e8f0;padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between">
                    <h6 class="mb-0 fw-700" style="font-size:.95rem"><i class="bi bi-hdd-network me-2" style="color:rgb(152,194,10)"></i>Asset Details</h6>
                    <button type="button" id="btnFetchScan" class="btn btn-sm" style="background:rgb(232,244,195);color:rgb(100,140,5);border:1.5px solid rgb(200,225,120);border-radius:8px;font-weight:600;font-size:.8rem">
                        <i class="bi bi-cloud-download me-1"></i> Fetch from Latest Scan
                    </button>
                </div>

                {{-- Scan fetch status banner --}}
                <div id="scanBanner" class="d-none px-3 pt-3">
                    <div id="scanBannerInner" class="alert py-2 mb-0" style="font-size:.82rem;border-radius:8px"></div>
                </div>

                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">IP Address <span class="text-danger">*</span></label>
                            <input type="text" name="ip_address" id="ip_address" class="form-control @error('ip_address') is-invalid @enderror"
                                   value="{{ old('ip_address', $asset->ip_address ?? '') }}"
                                   placeholder="e.g. 10.10.1.10" required autocomplete="off">
                            @error('ip_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div id="ipHint" class="form-text d-none" style="color:rgb(118,151,7)">
                                <i class="bi bi-arrow-up-circle me-1"></i>Click <strong>Fetch from Latest Scan</strong> to auto-fill hostname, OS, ports &amp; vuln counts.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold d-flex align-items-center gap-2">
                                Hostname
                                <span id="hostnameTag" class="d-none" style="font-size:.68rem;background:#dbeafe;color:#1e40af;padding:.1rem .45rem;border-radius:5px;font-weight:700">AUTO</span>
                            </label>
                            <input type="text" name="hostname" id="hostname" class="form-control @error('hostname') is-invalid @enderror"
                                   value="{{ old('hostname', $asset->hostname ?? '') }}"
                                   placeholder="Auto-detected from scan">
                            @error('hostname')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold d-flex align-items-center gap-2">
                                Operating System
                                <span id="osTag" class="d-none" style="font-size:.68rem;background:#dbeafe;color:#1e40af;padding:.1rem .45rem;border-radius:5px;font-weight:700">AUTO</span>
                            </label>
                            <input type="text" name="os" id="os" class="form-control @error('os') is-invalid @enderror"
                                   value="{{ old('os', $asset->os ?? '') }}"
                                   placeholder="Auto-detected from scan">
                            @error('os')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold d-flex align-items-center gap-2">
                                Open Ports / Services
                                <span id="portsTag" class="d-none" style="font-size:.68rem;background:#dbeafe;color:#1e40af;padding:.1rem .45rem;border-radius:5px;font-weight:700">AUTO</span>
                            </label>
                            <input type="text" name="open_ports" id="open_ports" class="form-control @error('open_ports') is-invalid @enderror"
                                   value="{{ old('open_ports', $asset->open_ports ?? '') }}"
                                   placeholder="Auto-detected from scan">
                            @error('open_ports')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tags / Keywords</label>
                            <input type="text" name="tags" id="tags" class="form-control @error('tags') is-invalid @enderror"
                                   value="{{ old('tags', $asset->tags ?? '') }}"
                                   placeholder="e.g. payment, card, internal, vendor">
                            <div class="form-text">Used for auto-classification (e.g. payment, pci, dmz, vendor)</div>
                            @error('tags')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold d-flex align-items-center gap-2">
                                Last Scanned At
                                <span id="scanSourceTag" class="d-none" style="font-size:.68rem;background:#dbeafe;color:#1e40af;padding:.1rem .45rem;border-radius:5px;font-weight:700;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title=""></span>
                            </label>
                            <input type="datetime-local" name="last_scanned_at" id="last_scanned_at" class="form-control @error('last_scanned_at') is-invalid @enderror"
                                   value="{{ old('last_scanned_at', isset($asset->last_scanned_at) ? $asset->last_scanned_at->format('Y-m-d\TH:i') : '') }}">
                            @error('last_scanned_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror"
                                      placeholder="Any additional context about this asset...">{{ old('notes', $asset->notes ?? '') }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Vulnerability Counts --}}
            <div class="card mb-4">
                <div class="card-header bg-white" style="border-radius:14px 14px 0 0;border-bottom:1px solid #e2e8f0;padding:1rem 1.25rem">
                    <h6 class="mb-0 fw-700" style="font-size:.95rem"><i class="bi bi-shield-exclamation me-2 text-danger"></i>Vulnerability Counts</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach([['vuln_critical','Critical','#dc2626','#fee2e2'],['vuln_high','High','#d97706','#fef3c7'],['vuln_medium','Medium','#2563eb','#dbeafe'],['vuln_low','Low','#374151','#f3f4f6']] as [$name,$label,$color,$bg])
                        <div class="col-6 col-md-3">
                            <label class="form-label fw-semibold" style="color:{{ $color }}">{{ $label }}</label>
                            <input type="number" name="{{ $name }}" id="{{ $name }}"
                                   class="form-control text-center fw-bold @error($name) is-invalid @enderror"
                                   style="background:{{ $bg }};color:{{ $color }};border-color:{{ $color }}44"
                                   value="{{ old($name, $asset->{$name} ?? 0) }}" min="0" placeholder="0">
                            @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>

        {{-- Right Column: Classification --}}
        <div class="col-lg-4">

            {{-- Auto-Classify Button --}}
            <div class="card mb-4" style="border:2px dashed rgb(152,194,10);border-radius:14px">
                <div class="card-body text-center py-4">
                    <i class="bi bi-magic" style="font-size:2rem;color:rgb(152,194,10)"></i>
                    <h6 class="mt-2 mb-1" style="font-weight:700">Auto-Classify</h6>
                    <p style="font-size:.82rem;color:#64748b;margin-bottom:1rem">
                        Fill in IP, hostname, ports, and tags above, then click to auto-detect scope, environment, system name, and classification level.
                    </p>
                    <button type="button" id="btnAutoClassify" class="btn text-white w-100 fw-semibold" style="background:rgb(152,194,10);border-radius:10px">
                        <i class="bi bi-magic me-1"></i> Auto-Classify Now
                    </button>
                    <div id="classifyAlert" class="alert mt-3 py-2 d-none" style="font-size:.82rem;border-radius:8px"></div>
                </div>
            </div>

            {{-- Classification Fields --}}
            <div class="card mb-4">
                <div class="card-header bg-white" style="border-radius:14px 14px 0 0;border-bottom:1px solid #e2e8f0;padding:1rem 1.25rem">
                    <h6 class="mb-0 fw-700" style="font-size:.95rem"><i class="bi bi-tags-fill me-2" style="color:rgb(152,194,10)"></i>Classification</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Identified Scope <span class="text-danger">*</span></label>
                        <select name="identified_scope" id="identified_scope" class="form-select @error('identified_scope') is-invalid @enderror" required>
                            @foreach(['PCI','DMZ','Internal','External','Third-Party'] as $s)
                                <option value="{{ $s }}" @selected(old('identified_scope', $asset->identified_scope ?? 'Internal') === $s)>{{ $s }}</option>
                            @endforeach
                        </select>
                        @error('identified_scope')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">PCI=payment/card | DMZ=internet-facing | Internal=private network</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Environment <span class="text-danger">*</span></label>
                        <select name="environment" id="environment" class="form-select @error('environment') is-invalid @enderror" required>
                            @foreach(['PROD','UAT','STAGE'] as $e)
                                <option value="{{ $e }}" @selected(old('environment', $asset->environment ?? 'PROD') === $e)>{{ $e }}</option>
                            @endforeach
                        </select>
                        @error('environment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">System Name</label>
                        <input type="text" name="system_name" id="system_name" class="form-control @error('system_name') is-invalid @enderror"
                               value="{{ old('system_name', $asset->system_name ?? '') }}"
                               placeholder="e.g. Core Banking, API Gateway">
                        @error('system_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Classification Level <span class="text-danger">*</span></label>
                        <select name="classification_level" id="classification_level" class="form-select @error('classification_level') is-invalid @enderror" required>
                            <option value="1" @selected(old('classification_level', $asset->classification_level ?? 3) == 1)>1 – Mission-Critical</option>
                            <option value="2" @selected(old('classification_level', $asset->classification_level ?? 3) == 2)>2 – Business-Critical</option>
                            <option value="3" @selected(old('classification_level', $asset->classification_level ?? 3) == 3)>3 – Business Operational</option>
                            <option value="4" @selected(old('classification_level', $asset->classification_level ?? 3) == 4)>4 – Administrative</option>
                            <option value="5" @selected(old('classification_level', $asset->classification_level ?? 3) == 5)>5 – None-Bank</option>
                        </select>
                        @error('classification_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Critical Level <span class="text-danger">*</span></label>
                        <select name="critical_level" id="critical_level" class="form-select @error('critical_level') is-invalid @enderror" required>
                            @foreach(['Mission-Critical','Business-Critical','Business Operational','Administrative','None-Bank'] as $cl)
                                <option value="{{ $cl }}" @selected(old('critical_level', $asset->critical_level ?? 'Business Operational') === $cl)>{{ $cl }}</option>
                            @endforeach
                        </select>
                        @error('critical_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            @foreach(['Active','Inactive','Decommissioned'] as $st)
                                <option value="{{ $st }}" @selected(old('status', $asset->status ?? 'Active') === $st)>{{ $st }}</option>
                            @endforeach
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            {{-- Hidden field for auto-classify flag --}}
            <input type="hidden" name="auto_classify" id="auto_classify" value="0">

            <div class="d-flex gap-2">
                <button type="submit" class="btn text-white flex-fill fw-semibold" style="background:rgb(152,194,10);border-radius:10px">
                    <i class="bi bi-{{ $editing ? 'check-lg' : 'plus-lg' }} me-1"></i>
                    {{ $editing ? 'Update Asset' : 'Add Asset' }}
                </button>
                <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary" style="border-radius:10px">Cancel</a>
            </div>

        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
const classifyLevelMap = {
    1: 'Mission-Critical',
    2: 'Business-Critical',
    3: 'Business Operational',
    4: 'Administrative',
    5: 'None-Bank',
};

document.getElementById('btnAutoClassify').addEventListener('click', async function () {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Classifying...';

    const payload = {
        ip_address:   document.getElementById('ip_address').value,
        hostname:     document.getElementById('hostname').value,
        open_ports:   document.getElementById('open_ports').value,
        tags:         document.getElementById('tags').value,
        vuln_critical:document.getElementById('vuln_critical').value,
        _token:       '{{ csrf_token() }}',
    };

    try {
        const res  = await fetch('{{ route("inventory.classify") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': payload._token },
            body: JSON.stringify(payload),
        });
        const data = await res.json();

        document.getElementById('identified_scope').value     = data.identified_scope;
        document.getElementById('environment').value          = data.environment;
        if (!document.getElementById('system_name').value) {
            document.getElementById('system_name').value      = data.system_name;
        }
        document.getElementById('classification_level').value = data.classification_level;
        document.getElementById('critical_level').value       = data.critical_level;

        const alert = document.getElementById('classifyAlert');
        alert.className = 'alert alert-success mt-3 py-2';
        alert.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Classified: <strong>'
            + data.identified_scope + '</strong> | <strong>' + data.environment
            + '</strong> | Level <strong>' + data.classification_level
            + ' – ' + data.critical_level + '</strong>';
        alert.classList.remove('d-none');
    } catch (e) {
        const alert = document.getElementById('classifyAlert');
        alert.className = 'alert alert-danger mt-3 py-2';
        alert.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i> Classification failed.';
        alert.classList.remove('d-none');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-magic me-1"></i> Auto-Classify Now';
    }
});

// Sync classification_level and critical_level dropdowns
document.getElementById('classification_level').addEventListener('change', function () {
    const map = { '1':'Mission-Critical','2':'Business-Critical','3':'Business Operational','4':'Administrative','5':'None-Bank' };
    document.getElementById('critical_level').value = map[this.value] || 'Business Operational';
});
document.getElementById('critical_level').addEventListener('change', function () {
    const map = { 'Mission-Critical':'1','Business-Critical':'2','Business Operational':'3','Administrative':'4','None-Bank':'5' };
    document.getElementById('classification_level').value = map[this.value] || '3';
});

// ── Fetch from latest scan ─────────────────────────────────────
const ipField = document.getElementById('ip_address');

// Show hint when IP is typed
ipField.addEventListener('input', function () {
    document.getElementById('ipHint').classList.toggle('d-none', !this.value.trim());
});

async function fetchScanData(ip) {
    const btn    = document.getElementById('btnFetchScan');
    const banner = document.getElementById('scanBanner');
    const inner  = document.getElementById('scanBannerInner');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Fetching...';
    banner.classList.add('d-none');

    try {
        const res  = await fetch('{{ route("inventory.scan-data") }}?ip=' + encodeURIComponent(ip));
        const data = await res.json();

        if (!data.found) {
            inner.className = 'alert alert-warning py-2 mb-0';
            inner.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i> '
                + (data.message || 'No scan data found for this IP.');
            banner.classList.remove('d-none');
            return;
        }

        // Fill hostname
        if (data.hostname) {
            document.getElementById('hostname').value = data.hostname;
            document.getElementById('hostnameTag').classList.remove('d-none');
        }
        // Fill OS
        if (data.os) {
            document.getElementById('os').value = data.os;
            document.getElementById('osTag').classList.remove('d-none');
        }
        // Fill open ports
        if (data.open_ports) {
            document.getElementById('open_ports').value = data.open_ports;
            document.getElementById('portsTag').classList.remove('d-none');
        }
        // Fill vuln counts
        ['critical','high','medium','low'].forEach(s => {
            const el = document.getElementById('vuln_' + s);
            if (el && data['vuln_' + s] !== undefined) el.value = data['vuln_' + s];
        });
        // Fill last scanned
        if (data.last_scanned) {
            const dt = data.last_scanned.replace(' ', 'T').substring(0, 16);
            document.getElementById('last_scanned_at').value = dt;
            const tag = document.getElementById('scanSourceTag');
            if (data.assessment) {
                tag.textContent = data.assessment;
                tag.title = 'Source: ' + data.assessment;
                tag.classList.remove('d-none');
            }
        }

        inner.className = 'alert alert-success py-2 mb-0';
        inner.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>'
            + ' Scan data loaded from <strong>' + (data.assessment || 'latest scan') + '</strong>'
            + ' &bull; Scanned: <strong>' + (data.last_scanned || '—') + '</strong>'
            + '<br><span style="font-size:.8rem">Hostname, OS, ports and vulnerability counts have been filled automatically. You can still edit them.</span>';
        banner.classList.remove('d-none');

        // Optionally trigger auto-classify after scan data is loaded
        document.getElementById('tags').dispatchEvent(new Event('input'));

    } catch (e) {
        inner.className = 'alert alert-danger py-2 mb-0';
        inner.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i> Failed to fetch scan data.';
        banner.classList.remove('d-none');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-cloud-download me-1"></i> Fetch from Latest Scan';
    }
}

// Button click
document.getElementById('btnFetchScan').addEventListener('click', function () {
    const ip = ipField.value.trim();
    if (!ip) {
        ipField.focus();
        ipField.classList.add('is-invalid');
        setTimeout(() => ipField.classList.remove('is-invalid'), 2000);
        return;
    }
    fetchScanData(ip);
});

// Auto-fetch on IP blur if field has value
ipField.addEventListener('blur', function () {
    if (this.value.trim()) fetchScanData(this.value.trim());
});
</script>
@endpush
