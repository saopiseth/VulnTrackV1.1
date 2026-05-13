@extends('layouts.app')
@section('title', 'Asset Inventory')

@section('content')
<style>
    .badge-pill  { padding:.2rem .6rem; border-radius:20px; font-size:.68rem; font-weight:700; display:inline-block; white-space:nowrap; }
    .tbl { min-width:1200px; }
    .tbl th { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px;
              color:#64748b; border-bottom:2px solid #e2e8f0; white-space:nowrap; }
    .tbl td { font-size:.83rem; vertical-align:middle; border-bottom:1px solid #f1f5f9; padding:.65rem .75rem; }
    .tbl tr:hover td { background:#fafafa; }
    .filter-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1rem 1.25rem; margin-bottom:1.25rem; }
    select.form-select, input.form-control { font-size:.83rem; border-radius:8px; }
    .scan-banner { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:.75rem 1.25rem;
                   display:flex; align-items:center; gap:.75rem; font-size:.85rem; margin-bottom:1.25rem; }
    .scan-banner.no-scan { background:#fef9c3; border-color:#fde68a; }
    .edit-btn { background:#f8fafc; border:1px solid #e2e8f0; border-radius:7px; color:#374151;
                padding:.25rem .55rem; font-size:.75rem; cursor:pointer; }
    .edit-btn:hover { background:#e0f2fe; border-color:#7dd3fc; color:#0369a1; }
</style>

{{-- Page header --}}
<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Asset Inventory</li>
        </ol></nav>
        <h4 class="mb-0"><i class="bi bi-pc-display-horizontal me-2" style="color:var(--primary)"></i>Asset Inventory</h4>
        <p class="mb-0">Hosts from the latest scan, enriched with business context.</p>
    </div>
    <a href="{{ route('asset-inventory.export.excel', request()->query()) }}"
       class="btn btn-sm" style="background:#16a34a;color:#fff;border-radius:8px">
        <i class="bi bi-file-earmark-excel-fill me-1"></i>Export Excel
    </a>
</div>

{{-- Latest scan banner --}}
@if($latestScan)
<div class="scan-banner">
    <i class="bi bi-check-circle-fill" style="color:#16a34a;font-size:1.1rem;flex-shrink:0"></i>
    <div>
        <strong>Latest scan:</strong>
        {{ $latestScan->filename }}
        &nbsp;·&nbsp;
        <span style="color:#64748b">{{ $latestScan->updated_at->format('d M Y, H:i') }}</span>
        &nbsp;·&nbsp;
        <span style="color:#166534;font-weight:600">{{ $assets->total() }} asset{{ $assets->total() == 1 ? '' : 's' }} total</span>
        &nbsp;·&nbsp;
        <span style="color:#64748b;font-size:.8rem">Assets not found on the latest scan date are marked <em>Not Found in Scan</em></span>
    </div>
</div>
@else
<div class="scan-banner no-scan">
    <i class="bi bi-exclamation-triangle-fill" style="color:#d97706;font-size:1.1rem;flex-shrink:0"></i>
    <div>No completed scan uploads found. Upload a Nessus scan to populate this inventory.</div>
</div>
@endif

{{-- Filters --}}
<form method="GET" action="{{ route('asset-inventory.index') }}" class="filter-card">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <input type="text" name="search" class="form-control" placeholder="Search IP, hostname, OS, owner…"
                   value="{{ request('search') }}">
        </div>
        <div class="col-md-2">
            <select name="scope" class="form-select">
                <option value="">All Scopes</option>
                @foreach($scopeOptions as $s)
                    <option value="{{ $s }}" @selected(request('scope') === $s)>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="env" class="form-select">
                <option value="">All Environments</option>
                @foreach($envOptions as $e)
                    <option value="{{ $e }}" @selected(request('env') === $e)>{{ $e }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="os_family" class="form-select">
                <option value="">All OS Families</option>
                @foreach(['Windows','Linux','Network','macOS'] as $fam)
                    <option value="{{ $fam }}" @selected(request('os_family') === $fam)>{{ $fam }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="">All Status</option>
                @foreach($statusOptions as $st)
                    <option value="{{ $st }}" @selected(request('status') === $st)>{{ $st }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-1 d-flex gap-1">
            <button type="submit" class="btn btn-sm w-100" style="background:var(--primary);color:#fff;border-radius:8px">
                <i class="bi bi-funnel-fill"></i>
            </button>
            @if(request()->hasAny(['search','scope','env','status','os_family']))
            <a href="{{ route('asset-inventory.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px">
                <i class="bi bi-x"></i>
            </a>
            @endif
        </div>
    </div>
</form>

{{-- Table --}}
<div class="card">
    <div class="card-body p-0">
        @if($assets->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-hdd-stack" style="font-size:2.5rem;opacity:.3"></i>
            <p class="mt-2 mb-0">No assets found{{ request()->hasAny(['search','scope','env','status','os_family']) ? ' for the selected filters' : '' }}.</p>
        </div>
        @else
        <div class="table-responsive">
            <table class="table tbl mb-0">
                <thead>
                    <tr>
                        <th class="ps-3" style="width:46px">#</th>
                        <th style="width:130px">IP Address</th>
                        <th style="width:160px">Hostname</th>
                        <th style="width:200px">Operating System</th>
                        <th style="width:160px">Kernel / Build</th>
                        <th style="width:160px">Role / Functionality</th>
                        <th style="width:110px">Scope</th>
                        <th style="width:110px">Environment</th>
                        <th style="width:140px">Asset Owner</th>
                        <th style="width:150px">Remediation SLA</th>
                        <th style="width:170px">Status</th>
                        <th class="pe-3" style="width:80px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($assets as $asset)
                @php
                    $badge = $asset->osFamilyBadge();
                    $scopeColors = ['PCI'=>['#fef2f2','#991b1b'],'Swift'=>['#ecfeff','#0e7490'],
                        'Non-Bank'=>['#f0fdf4','#166534'],'Public'=>['#fffbeb','#92400e'],
                        'Critical'=>['#fff1f2','#be123c'],'Less Critical'=>['#faf5ff','#6d28d9']];
                    [$sbg,$scol] = $scopeColors[$asset->identified_scope] ?? ['#f1f5f9','#475569'];
                    $envColors = ['PROD'=>['#fee2e2','#991b1b'],'UAT'=>['#fef9c3','#854d0e'],'STAGE'=>['#f1f5f9','#475569'],
                        'DR'=>['#fff7ed','#9a3412'],'DEV'=>['#ecfeff','#0e7490'],
                        'Non-Prod'=>['#eef2ff','#3730a3'],'DEV-QA'=>['#fdf4ff','#7e22ce']];
                    [$ebg,$ecol] = $envColors[$asset->environment] ?? ['#f1f5f9','#475569'];
                    $slaColors = ['Priority Level 1'=>['#fee2e2','#991b1b'],'Priority Level 2'=>['#fef3c7','#92400e'],
                        'Priority Level 3'=>['#e0f2fe','#0c4a6e'],'Priority Level 4'=>['#f0fdf4','#166534']];
                    [$slabg,$slacol] = $slaColors[$asset->remediation_sla] ?? ['#f1f5f9','#475569'];
                    $statusColors = ['Active'=>['#dcfce7','#166534'],'Not Found in Scan'=>['#fef3c7','#92400e'],
                        'Not Found in Latest Scan'=>['#fef3c7','#92400e'],'Decommissioned'=>['#fee2e2','#991b1b']];
                    [$stbg,$stcol] = $statusColors[$asset->status] ?? ['#f1f5f9','#475569'];
                @endphp
                <tr data-id="{{ $asset->id }}"
                    data-system="{{ $asset->system_name }}"
                    data-scope="{{ $asset->identified_scope }}"
                    data-env="{{ $asset->environment }}"
                    data-owner="{{ $asset->system_owner }}"
                    data-sla="{{ $asset->remediation_sla }}">
                    <td class="ps-3" style="color:#94a3b8;font-size:.78rem">{{ $assets->firstItem() + $loop->index }}</td>

                    {{-- IP --}}
                    <td><span style="font-family:monospace;font-weight:600;color:#0f172a">{{ $asset->ip_address }}</span></td>

                    {{-- Hostname --}}
                    <td style="color:#374151;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                        title="{{ $asset->hostname }}">{{ $asset->hostname ?: '—' }}</td>

                    {{-- OS --}}
                    <td>
                        @if($asset->os)
                        <span style="display:flex;align-items:flex-start;gap:4px;flex-wrap:wrap">
                            <span style="background:{{ $badge['bg'] }};color:{{ $badge['color'] }};padding:.15rem .4rem;border-radius:6px;font-size:.68rem;font-weight:700;white-space:nowrap">
                                <i class="bi {{ $badge['icon'] }}"></i> {{ $asset->os_family ?? 'Unknown' }}
                            </span>
                            <span style="color:#374151;font-size:.79rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:130px" title="{{ $asset->os }}">{{ $asset->os }}</span>
                        </span>
                        @else <span class="text-muted">—</span> @endif
                    </td>

                    {{-- Kernel --}}
                    <td>
                        @if($asset->os_kernel)
                            <code style="font-size:.74rem;background:#f1f5f9;padding:.1rem .4rem;border-radius:5px;color:#374151;
                                         display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:155px"
                                  title="{{ $asset->os_kernel }}">{{ $asset->os_kernel }}</code>
                        @else <span class="text-muted">—</span> @endif
                    </td>

                    {{-- Role / Functionality --}}
                    <td class="js-cell-system" style="color:#374151;max-width:155px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                        title="{{ $asset->system_name }}">{{ $asset->system_name ?: '—' }}</td>

                    {{-- Scope --}}
                    <td class="js-cell-scope">
                        @if($asset->identified_scope)
                        <span class="badge-pill" style="background:{{ $sbg }};color:{{ $scol }}">{{ $asset->identified_scope }}</span>
                        @else <span class="text-muted">—</span> @endif
                    </td>

                    {{-- Environment --}}
                    <td class="js-cell-env">
                        @if($asset->environment)
                        <span class="badge-pill" style="background:{{ $ebg }};color:{{ $ecol }}">{{ $asset->environment }}</span>
                        @else <span class="text-muted">—</span> @endif
                    </td>

                    {{-- Asset Owner --}}
                    <td class="js-cell-owner" style="color:#374151;max-width:135px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                        title="{{ $asset->system_owner }}">{{ $asset->system_owner ?: '—' }}</td>

                    {{-- Remediation SLA --}}
                    <td class="js-cell-sla">
                        @if($asset->remediation_sla)
                        <span class="badge-pill" style="background:{{ $slabg }};color:{{ $slacol }}">{{ $asset->remediation_sla }}</span>
                        @else <span class="text-muted">—</span> @endif
                    </td>

                    {{-- Status --}}
                    <td>
                        <span class="badge-pill" style="background:{{ $stbg }};color:{{ $stcol }}">{{ $asset->status ?? 'Active' }}</span>
                    </td>

                    {{-- Actions --}}
                    <td class="pe-3">
                        <div class="d-flex gap-1">
                            <button type="button" class="edit-btn js-edit-asset"
                                    title="Edit business fields">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <a href="{{ route('asset-inventory.show', $asset) }}"
                               class="edit-btn" title="View detail" style="text-decoration:none">
                                <i class="bi bi-eye-fill" style="color:var(--primary)"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($assets->hasPages())
        <div class="px-3 py-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span class="text-muted" style="font-size:.8rem">
                Showing {{ $assets->firstItem() }}–{{ $assets->lastItem() }} of {{ $assets->total() }} assets
            </span>
            {{ $assets->links('pagination::bootstrap-5') }}
        </div>
        @endif
        @endif
    </div>
</div>

{{-- ════ Edit Business Fields Modal ════ --}}
<div class="modal fade" id="editAssetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:500px">
        <div class="modal-content" style="border-radius:16px;border:none">
            <div class="modal-header px-4 pt-4 pb-3" style="border-bottom:1px solid #f1f5f9">
                <div>
                    <h5 class="modal-title mb-0" style="font-weight:700">Edit Business Fields</h5>
                    <p class="mb-0 mt-1" id="editAssetIp" style="font-size:.8rem;color:#64748b;font-family:monospace"></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <div id="editAssetError" class="alert alert-danger d-none py-2" style="font-size:.85rem;border-radius:8px"></div>

                <div class="mb-3">
                    <label class="form-label" style="font-size:.8rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px">Role / Functionality</label>
                    <input type="text" id="edit_system_name" class="form-control" placeholder="e.g. Web Server, Database, Firewall" style="border-radius:8px">
                </div>
                <div class="mb-3">
                    <label class="form-label" style="font-size:.8rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px">Identified Scope</label>
                    <select id="edit_scope" class="form-select" style="border-radius:8px">
                        <option value="">— None —</option>
                        @foreach($scopeOptions as $s)
                        <option value="{{ $s }}">{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" style="font-size:.8rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px">Environment</label>
                    <select id="edit_env" class="form-select" style="border-radius:8px">
                        <option value="">— None —</option>
                        @foreach($envOptions as $e)
                        <option value="{{ $e }}">{{ $e }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" style="font-size:.8rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px">Asset Owner</label>
                    <input type="text" id="edit_owner" class="form-control" placeholder="e.g. IT Department, John Doe" style="border-radius:8px">
                </div>
                <div class="mb-1">
                    <label class="form-label" style="font-size:.8rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px">Remediation SLA</label>
                    <select id="edit_sla" class="form-select" style="border-radius:8px">
                        <option value="">— None —</option>
                        @foreach($slaOptions as $sl)
                        <option value="{{ $sl }}">{{ $sl }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer px-4 pb-4" style="border-top:1px solid #f1f5f9">
                <button type="button" class="btn" data-bs-dismiss="modal"
                        style="border-color:#e2e8f0;color:#64748b;border-radius:10px;font-size:.875rem">Cancel</button>
                <button type="button" id="saveAssetBtn" class="btn"
                        style="background:var(--primary);color:#fff;border-radius:10px;font-size:.875rem;font-weight:600;min-width:100px">
                    <span id="saveBtnText"><i class="bi bi-floppy-fill me-1"></i>Save</span>
                    <span id="saveBtnSpinner" class="d-none spinner-border spinner-border-sm"></span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Toast --}}
<div id="assetToast" class="toast align-items-center border-0 position-fixed bottom-0 end-0 m-3"
     role="alert" style="z-index:9999;background:#166534;color:#fff;border-radius:12px">
    <div class="d-flex">
        <div class="toast-body fw-600"><i class="bi bi-check-circle-fill me-2"></i>Asset updated successfully.</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
</div>

<script>
(function () {
    const modal    = new bootstrap.Modal(document.getElementById('editAssetModal'));
    const toast    = new bootstrap.Toast(document.getElementById('assetToast'), { delay: 3000 });
    const errDiv   = document.getElementById('editAssetError');
    const saveBtn  = document.getElementById('saveAssetBtn');
    const saveTxt  = document.getElementById('saveBtnText');
    const saveSpin = document.getElementById('saveBtnSpinner');

    const scopeColors = {
        'PCI':          ['#fef2f2','#991b1b'], 'Swift':         ['#ecfeff','#0e7490'],
        'Non-Bank':     ['#f0fdf4','#166534'], 'Public':        ['#fffbeb','#92400e'],
        'Critical':     ['#fff1f2','#be123c'], 'Less Critical': ['#faf5ff','#6d28d9'],
    };
    const envColors = {
        'PROD':['#fee2e2','#991b1b'], 'UAT':['#fef9c3','#854d0e'], 'STAGE':['#f1f5f9','#475569'],
        'DR':['#fff7ed','#9a3412'],   'DEV':['#ecfeff','#0e7490'],
        'Non-Prod':['#eef2ff','#3730a3'], 'DEV-QA':['#fdf4ff','#7e22ce'],
    };
    const slaColors = {
        'Priority Level 1':['#fee2e2','#991b1b'], 'Priority Level 2':['#fef3c7','#92400e'],
        'Priority Level 3':['#e0f2fe','#0c4a6e'], 'Priority Level 4':['#f0fdf4','#166534'],
    };

    let currentRow = null;
    let currentId  = null;

    function makeBadge(value, colorMap) {
        if (!value) return '<span class="text-muted">—</span>';
        const [bg, color] = colorMap[value] ?? ['#f1f5f9', '#475569'];
        return `<span class="badge-pill" style="background:${bg};color:${color}">${esc(value)}</span>`;
    }

    function esc(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    document.querySelectorAll('.js-edit-asset').forEach(btn => {
        btn.addEventListener('click', function () {
            currentRow = this.closest('tr');
            currentId  = currentRow.dataset.id;
            const ip   = currentRow.querySelector('td:nth-child(2)').textContent.trim();

            document.getElementById('editAssetIp').textContent   = ip;
            document.getElementById('edit_system_name').value    = currentRow.dataset.system || '';
            document.getElementById('edit_scope').value          = currentRow.dataset.scope  || '';
            document.getElementById('edit_env').value            = currentRow.dataset.env    || '';
            document.getElementById('edit_owner').value          = currentRow.dataset.owner  || '';
            document.getElementById('edit_sla').value            = currentRow.dataset.sla    || '';
            errDiv.classList.add('d-none');
            modal.show();
        });
    });

    saveBtn.addEventListener('click', async function () {
        saveTxt.classList.add('d-none');
        saveSpin.classList.remove('d-none');
        errDiv.classList.add('d-none');
        saveBtn.disabled = true;

        const payload = {
            _method:        'PUT',
            system_name:    document.getElementById('edit_system_name').value.trim(),
            identified_scope: document.getElementById('edit_scope').value,
            environment:    document.getElementById('edit_env').value,
            system_owner:   document.getElementById('edit_owner').value.trim(),
            remediation_sla: document.getElementById('edit_sla').value,
        };

        try {
            const res = await fetch(`/asset-inventory/${currentId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-HTTP-Method-Override': 'PUT',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            const data = await res.json();

            if (!res.ok) {
                const msgs = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Failed to save.');
                errDiv.textContent = msgs;
                errDiv.classList.remove('d-none');
                return;
            }

            const a = data.asset;

            // Update row data attrs
            currentRow.dataset.system = a.system_name    || '';
            currentRow.dataset.scope  = a.identified_scope || '';
            currentRow.dataset.env    = a.environment    || '';
            currentRow.dataset.owner  = a.system_owner   || '';
            currentRow.dataset.sla    = a.remediation_sla || '';

            // Update visible cells
            currentRow.querySelector('.js-cell-system').textContent = a.system_name || '—';
            currentRow.querySelector('.js-cell-system').title       = a.system_name || '';
            currentRow.querySelector('.js-cell-scope').innerHTML    = makeBadge(a.identified_scope, scopeColors);
            currentRow.querySelector('.js-cell-env').innerHTML      = makeBadge(a.environment, envColors);
            currentRow.querySelector('.js-cell-owner').textContent  = a.system_owner || '—';
            currentRow.querySelector('.js-cell-owner').title        = a.system_owner || '';
            currentRow.querySelector('.js-cell-sla').innerHTML      = makeBadge(a.remediation_sla, slaColors);

            modal.hide();
            toast.show();

        } catch (e) {
            errDiv.textContent = 'Network error. Please try again.';
            errDiv.classList.remove('d-none');
        } finally {
            saveTxt.classList.remove('d-none');
            saveSpin.classList.add('d-none');
            saveBtn.disabled = false;
        }
    });
})();
</script>
@endsection
