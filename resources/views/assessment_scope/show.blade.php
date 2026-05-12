@extends('layouts.app')
@section('title', $group->name)

@section('content')

{{-- ── Header ── --}}
<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('assessment-scope.index') }}"
           style="width:36px;height:36px;border-radius:10px;background:#f8fafc;border:1.5px solid #e2e8f0;display:flex;align-items:center;justify-content:center;color:#64748b;text-decoration:none;flex-shrink:0"
           onmouseover="this.style.background='rgb(240,248,210)'" onmouseout="this.style.background='#f8fafc'">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h4 class="mb-0">{{ $group->name }}</h4>
            <p class="mb-0">
                {{ $stats['total'] }} asset{{ $stats['total'] == 1 ? '' : 's' }} in scope
                @if($group->description)
                &nbsp;·&nbsp; {{ $group->description }}
                @endif
            </p>
        </div>
    </div>
    <div class="d-flex gap-2">
        <button class="btn" id="exportBtn"
                style="border-radius:10px;font-size:.875rem;font-weight:600;border:1.5px solid #e2e8f0;color:#374151;background:#fff">
            <i class="bi bi-file-earmark-arrow-down me-1"></i> Export
        </button>
        <button class="btn btn-outline-secondary" id="importOpenBtn"
                style="border-radius:10px;font-size:.875rem;font-weight:600;border-color:#e2e8f0;color:#374151">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Import
        </button>
        <button class="btn btn-primary" id="addOpenBtn"
                style="background:var(--primary);border-color:var(--primary);border-radius:10px;font-size:.875rem;font-weight:600">
            <i class="bi bi-plus-lg me-1"></i> Add Asset
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert d-flex align-items-center gap-2 mb-4" style="border-radius:12px;border:none;background:#f0fdf4;color:#166534;">
    <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
</div>
@endif

{{-- ── Scope stats strip ── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center py-3 px-2">
            <div style="font-size:1.7rem;font-weight:800;color:#0f172a">{{ $stats['total'] }}</div>
            <div style="font-size:.75rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Total</div>
        </div>
    </div>
    @foreach(['PCI'=>'#dc2626','DMZ'=>'#d97706','Internal'=>'#2563eb'] as $s => $c)
    <div class="col-6 col-md">
        <div class="card text-center py-3 px-2" style="border-top:3px solid {{ $c }}">
            <div style="font-size:1.4rem;font-weight:800;color:#0f172a">{{ $stats['by_scope'][$s] ?? 0 }}</div>
            <div style="font-size:.72rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.5px">{{ $s }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- ── Items table ── --}}
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle" style="font-size:.85rem">
                <thead style="background:#f8fafc;border-bottom:2px solid #e2e8f0">
                    <tr>
                        <th class="px-4 py-3" style="color:#64748b;font-weight:700;font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;width:52px">No</th>
                        <th class="py-3" style="color:#64748b;font-weight:700;font-size:.75rem;text-transform:uppercase;letter-spacing:.5px">Host Name</th>
                        <th class="py-3" style="color:#64748b;font-weight:700;font-size:.75rem;text-transform:uppercase;letter-spacing:.5px">IP Address</th>
                        <th class="py-3" style="color:#64748b;font-weight:700;font-size:.75rem;text-transform:uppercase;letter-spacing:.5px">Identify Scope</th>
                        <th class="py-3" style="color:#64748b;font-weight:700;font-size:.75rem;text-transform:uppercase;letter-spacing:.5px">Role / Functionality</th>
                        <th class="py-3" style="color:#64748b;font-weight:700;font-size:.75rem;text-transform:uppercase;letter-spacing:.5px">Environment</th>
                        <th class="py-3" style="color:#64748b;font-weight:700;font-size:.75rem;text-transform:uppercase;letter-spacing:.5px">Asset Owner</th>
                        <th class="py-3" style="color:#64748b;font-weight:700;font-size:.75rem;text-transform:uppercase;letter-spacing:.5px">Remediation SLA</th>
                        <th class="py-3 pe-4" style="color:#64748b;font-weight:700;font-size:.75rem;text-transform:uppercase;letter-spacing:.5px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($items as $item)
                    @php $scopeColors = ['PCI'=>['#fef2f2','#991b1b'],'DMZ'=>['#fffbeb','#92400e'],'Internal'=>['#eff6ff','#1e40af']]; $sc = $scopeColors[$item->identified_scope] ?? null; @endphp
                    <tr style="border-bottom:1px solid #f1f5f9"
                        data-id="{{ $item->id }}"
                        data-ip="{{ $item->ip_address }}"
                        data-hostname="{{ $item->hostname }}"
                        data-system="{{ $item->system_name }}"
                        data-criticality="{{ $item->system_criticality }}"
                        data-owner="{{ $item->system_owner }}"
                        data-scope="{{ $item->identified_scope }}"
                        data-env="{{ $item->environment }}"
                        data-loc="{{ $item->location }}"
                        data-notes="{{ $item->notes }}"
                        data-sla="{{ $item->remediation_sla }}">
                        <td class="px-4 py-3" style="color:#94a3b8;font-size:.8rem">{{ $items->firstItem() + $loop->index }}</td>
                        <td class="py-3" style="color:#374151;font-weight:600">{{ $item->hostname ?: '—' }}</td>
                        <td class="py-3" style="font-family:monospace;color:#0f172a;font-weight:600">{{ $item->ip_address ?: '—' }}</td>
                        <td class="py-3">
                            @if($item->identified_scope && $sc)
                            <span style="background:{{ $sc[0] }};color:{{ $sc[1] }};font-size:.72rem;font-weight:700;padding:.2rem .55rem;border-radius:20px">{{ $item->identified_scope }}</span>
                            @else<span style="color:#94a3b8">—</span>@endif
                        </td>
                        <td class="py-3" style="color:#374151;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $item->system_name }}">{{ $item->system_name ?: '—' }}</td>
                        <td class="py-3">
                            @if($item->environment)
                            <span style="background:#f0fdf4;color:#166534;font-size:.72rem;font-weight:700;padding:.2rem .55rem;border-radius:20px">{{ $item->environment }}</span>
                            @else<span style="color:#94a3b8">—</span>@endif
                        </td>
                        <td class="py-3" style="color:#374151">{{ $item->system_owner ?: '—' }}</td>
                        <td class="py-3">
                            @if($item->remediation_sla)
                            <span style="background:#eff6ff;color:#1e40af;font-size:.72rem;font-weight:700;padding:.2rem .55rem;border-radius:20px;white-space:nowrap">{{ $item->remediation_sla }}</span>
                            @else<span style="color:#94a3b8">—</span>@endif
                        </td>
                        <td class="py-3 pe-4">
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm js-edit-item"
                                        style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;color:#374151;padding:.3rem .6rem">
                                    <i class="bi bi-pencil-fill" style="font-size:.75rem"></i>
                                </button>
                                <form method="POST" action="{{ route('assessment-scope.items.destroy', [$group, $item]) }}"
                                      onsubmit="return confirm('Delete this entry?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm"
                                            style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;color:#dc2626;padding:.3rem .6rem">
                                        <i class="bi bi-trash3-fill" style="font-size:.75rem"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div style="color:#94a3b8">
                                <i class="bi bi-hdd-stack" style="font-size:2rem;display:block;margin-bottom:.5rem"></i>
                                No assets yet. Add manually or import from Excel.
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
        <div class="px-4 py-3 border-top" style="border-color:#f1f5f9!important">{{ $items->links() }}</div>
        @endif
    </div>
</div>


{{-- ════ Add Modal ════ --}}
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;border:none">
            <div class="modal-header px-4 pt-4 pb-3" style="border-bottom:1px solid #f1f5f9">
                <h5 class="modal-title" style="font-weight:700">Add Asset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('assessment-scope.items.store', $group) }}">
                @csrf
                <div class="modal-body px-4 py-3">
                    @include('assessment_scope._item_fields', ['prefix' => 'add'])
                </div>
                <div class="modal-footer px-4 pb-4" style="border-top:1px solid #f1f5f9">
                    <button type="button" class="btn" data-bs-dismiss="modal"
                            style="border-color:#e2e8f0;color:#64748b;border-radius:10px;font-size:.875rem">Cancel</button>
                    <button type="submit" class="btn"
                            style="background:var(--primary);color:#fff;border-radius:10px;font-size:.875rem;font-weight:600">
                        <i class="bi bi-plus-lg me-1"></i> Add Asset
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ════ Edit Modal ════ --}}
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;border:none">
            <div class="modal-header px-4 pt-4 pb-3" style="border-bottom:1px solid #f1f5f9">
                <h5 class="modal-title" style="font-weight:700">Edit Asset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editForm">
                @csrf @method('PATCH')
                <div class="modal-body px-4 py-3">
                    @include('assessment_scope._item_fields', ['prefix' => 'edit'])
                </div>
                <div class="modal-footer px-4 pb-4" style="border-top:1px solid #f1f5f9">
                    <button type="button" class="btn" data-bs-dismiss="modal"
                            style="border-color:#e2e8f0;color:#64748b;border-radius:10px;font-size:.875rem">Cancel</button>
                    <button type="submit" class="btn"
                            style="background:#0f172a;color:#fff;border-radius:10px;font-size:.875rem;font-weight:600">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ════ Import Modal ════ --}}
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;border:none">
            <div class="modal-header px-4 pt-4 pb-3" style="border-bottom:1px solid #f1f5f9">
                <div>
                    <h5 class="modal-title fw-700 mb-0" style="font-weight:700">Import from Excel / CSV</h5>
                    <div style="font-size:.8rem;color:#64748b;margin-top:.25rem">Upload a .xlsx, .xls, or .csv file. Map columns then click Import.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-4">
                <div id="import-step1">
                    <div id="dropzone"
                         style="border:2px dashed #e2e8f0;border-radius:14px;padding:3rem;text-align:center;cursor:pointer;transition:all .2s">
                        <i class="bi bi-file-earmark-spreadsheet" style="font-size:2.5rem;color:var(--primary);display:block;margin-bottom:.75rem"></i>
                        <div style="font-weight:600;color:#0f172a;margin-bottom:.25rem">Drop your file here or click to browse</div>
                        <div style="font-size:.82rem;color:#94a3b8">Supports .xlsx, .xls, .csv — max 5 MB</div>
                    </div>
                    <input type="file" id="fileInput" accept=".xlsx,.xls,.csv" class="d-none">
                    <div class="mt-3 p-3" style="background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                            <div style="font-size:.8rem;font-weight:700;color:#374151">Expected columns:</div>
                            <button type="button" id="downloadTemplateBtn"
                                    style="background:var(--primary);color:#fff;border:none;border-radius:8px;font-size:.78rem;font-weight:600;padding:.3rem .85rem;cursor:pointer">
                                <i class="bi bi-download me-1"></i> Download Template
                            </button>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach(['hostname','ip_address','identified_scope','system_name','environment','system_owner','remediation_sla'] as $col)
                            <code style="background:#e2e8f0;padding:.1rem .45rem;border-radius:5px;font-size:.75rem;color:#374151">{{ $col }}</code>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div id="import-step2" style="display:none">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <span id="import-filename" style="font-weight:600;color:#0f172a"></span>
                            <span id="import-rowcount" style="font-size:.82rem;color:#64748b;margin-left:.5rem"></span>
                        </div>
                        <button class="btn btn-sm" id="changeFileBtn"
                                style="border-color:#e2e8f0;color:#64748b;border-radius:8px;font-size:.8rem">
                            <i class="bi bi-arrow-left me-1"></i> Change File
                        </button>
                    </div>
                    <div class="p-3 mb-3" style="background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0">
                        <div style="font-size:.82rem;font-weight:700;color:#374151;margin-bottom:.75rem">Map columns</div>
                        <div class="row g-2" id="column-map-grid"></div>
                    </div>
                    <div style="max-height:280px;overflow:auto;border-radius:10px;border:1px solid #e2e8f0">
                        <table class="table table-sm mb-0" style="font-size:.78rem" id="preview-table">
                            <thead style="background:#f8fafc;position:sticky;top:0"></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div id="import-error" class="mt-3 alert" style="display:none;border-radius:10px;border:none;background:#fef2f2;color:#991b1b;font-size:.85rem"></div>
                </div>
            </div>
            <div class="modal-footer px-4 pb-4" style="border-top:1px solid #f1f5f9">
                <button type="button" class="btn" data-bs-dismiss="modal"
                        style="border-color:#e2e8f0;color:#64748b;border-radius:10px;font-size:.875rem">Cancel</button>
                <button type="button" id="importBtn" style="display:none;background:var(--primary);color:#fff;border:none;border-radius:10px;font-size:.875rem;font-weight:600;padding:.45rem 1.1rem">
                    <span id="importBtnLabel"><i class="bi bi-upload me-1"></i> Import</span>
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script nonce="{{ csp_nonce() }}">
document.addEventListener('DOMContentLoaded', function () {

const FIELDS = ['hostname','ip_address','identified_scope','system_name','environment','system_owner','remediation_sla'];
const FIELD_LABELS = {hostname:'Host Name',ip_address:'IP Address',identified_scope:'Identify Scope',system_name:'Role / Functionality',environment:'Environment',system_owner:'Asset Owner',remediation_sla:'Remediation SLA'};
const IMPORT_URL = '{{ route("assessment-scope.import", $group) }}';
const EXPORT_URL = '{{ route("assessment-scope.export", $group) }}';

const addModal    = new bootstrap.Modal(document.getElementById('addModal'));
const editModal   = new bootstrap.Modal(document.getElementById('editModal'));
const importModal = new bootstrap.Modal(document.getElementById('importModal'));

// ── Header buttons ────────────────────────────────────────────
document.getElementById('addOpenBtn').addEventListener('click', function () { addModal.show(); });
document.getElementById('importOpenBtn').addEventListener('click', function () { importModal.show(); });

// ── Export ────────────────────────────────────────────────────
document.getElementById('exportBtn').addEventListener('click', function () {
    const btn = this;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Exporting…';
    btn.disabled = true;
    fetch(EXPORT_URL, { headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (rows) {
            const wsData = [FIELDS, ...rows.map(function (r) { return FIELDS.map(function (h) { return r[h] ?? ''; }); })];
            const ws = XLSX.utils.aoa_to_sheet(wsData);
            ws['!cols'] = [{wch:20},{wch:16},{wch:14},{wch:28},{wch:12},{wch:20},{wch:16}];
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Scope');
            XLSX.writeFile(wb, '{{ Str::slug($group->name) }}_' + new Date().toISOString().slice(0,10) + '.xlsx');
        })
        .catch(function (err) { alert('Export failed: ' + err.message); })
        .finally(function () { btn.innerHTML = '<i class="bi bi-file-earmark-arrow-down me-1"></i> Export'; btn.disabled = false; });
});

// ── Download Template ─────────────────────────────────────────
document.getElementById('downloadTemplateBtn').addEventListener('click', function () {
    const samples = [
        ['web-server-01','192.168.1.10','PCI','Core Banking System','PROD','IT Department','30 days'],
        ['db-server-02','10.0.0.25','Internal','Customer Database','PROD','DBA Team','60 days'],
        ['app-server-03','172.16.5.50','DMZ','API Gateway','UAT','Dev Team','30 days'],
    ];
    const ws = XLSX.utils.aoa_to_sheet([FIELDS, ...samples]);
    ws['!cols'] = [{wch:20},{wch:16},{wch:14},{wch:28},{wch:12},{wch:20},{wch:16}];
    const wsRef = XLSX.utils.aoa_to_sheet([
        ['Field','Allowed Values'],
        ['ip_address','IPv4 or IPv6  e.g. 192.168.1.10'],
        ['identified_scope','PCI | DMZ | Internal'],
        ['environment','PROD | UAT | STAGE'],
        ['remediation_sla','Free text  e.g. 15 days | 30 days | 60 days | 90 days'],
    ]);
    wsRef['!cols'] = [{wch:22},{wch:70}];
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Scope');
    XLSX.utils.book_append_sheet(wb, wsRef, 'Reference');
    XLSX.writeFile(wb, 'assessment_scope_template.xlsx');
});

// ── Edit row ──────────────────────────────────────────────────
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.js-edit-item');
    if (!btn) return;
    const d = btn.closest('tr').dataset;
    document.getElementById('edit-hostname').value    = d.hostname    || '';
    document.getElementById('edit-ip').value          = d.ip          || '';
    document.getElementById('edit-scope').value       = d.scope       || '';
    document.getElementById('edit-system').value      = d.system      || '';
    document.getElementById('edit-env').value         = d.env         || '';
    document.getElementById('edit-owner').value       = d.owner       || '';
    document.getElementById('edit-sla').value         = d.sla         || '';
    document.getElementById('edit-criticality').value = d.criticality || '';
    document.getElementById('edit-loc').value         = d.loc         || '';
    document.getElementById('edit-notes').value       = d.notes       || '';
    document.getElementById('editForm').action = '/assessment-scope/{{ $group->id }}/items/' + d.id;
    editModal.show();
});

// ── Import: dropzone ──────────────────────────────────────────
let parsedRows = [], fileHeaders = [], columnMap = {};

const dropzone  = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');

dropzone.addEventListener('click', function () { fileInput.click(); });
dropzone.addEventListener('dragover', function (e) { e.preventDefault(); dropzone.style.borderColor = 'var(--primary)'; });
dropzone.addEventListener('dragleave', function () { dropzone.style.borderColor = '#e2e8f0'; });
dropzone.addEventListener('drop', function (e) {
    e.preventDefault();
    dropzone.style.borderColor = '#e2e8f0';
    if (e.dataTransfer.files[0]) readFile(e.dataTransfer.files[0]);
});
fileInput.addEventListener('change', function (e) { if (e.target.files[0]) readFile(e.target.files[0]); });

function readFile(file) {
    if (file.size > 5 * 1024 * 1024) { alert('File too large'); return; }
    const reader = new FileReader();
    reader.onload = function (e) {
        const wb  = XLSX.read(e.target.result, { type: 'array' });
        const raw = XLSX.utils.sheet_to_json(wb.Sheets[wb.SheetNames[0]], { header: 1, defval: '' });
        if (raw.length < 2) { alert('File appears empty.'); return; }
        fileHeaders = raw[0].map(function (h) { return String(h).trim(); });
        parsedRows  = raw.slice(1).filter(function (r) { return r.some(function (c) { return c !== ''; }); });
        renderStep2(file.name);
    };
    reader.readAsArrayBuffer(file);
}

function renderStep2(name) {
    document.getElementById('import-step1').style.display = 'none';
    document.getElementById('import-step2').style.display = '';
    document.getElementById('import-filename').textContent = name;
    document.getElementById('import-rowcount').textContent = '(' + parsedRows.length + ' rows)';
    document.getElementById('importBtn').style.display = '';
    buildColumnMap();
    buildPreview();
}

function buildColumnMap() {
    columnMap = {};
    const norm = function (s) { return s.toLowerCase().replace(/[\s_]/g, ''); };
    FIELDS.forEach(function (f) {
        const idx = fileHeaders.findIndex(function (h) { return norm(h) === norm(f); });
        columnMap[f] = idx >= 0 ? idx : -1;
    });
    const grid = document.getElementById('column-map-grid');
    grid.innerHTML = '';
    FIELDS.forEach(function (f) {
        const wrap = document.createElement('div');
        wrap.className = 'col-6 col-md-4';

        const label = document.createElement('label');
        label.style.cssText = 'font-size:.72rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.3px';
        label.textContent = FIELD_LABELS[f];

        const sel = document.createElement('select');
        sel.className = 'form-select form-select-sm';
        sel.dataset.field = f;
        sel.style.cssText = 'border-radius:8px;font-size:.8rem;border-color:#e2e8f0;margin-top:.2rem';

        const skip = document.createElement('option');
        skip.value = '-1'; skip.textContent = '— skip —';
        sel.appendChild(skip);

        fileHeaders.forEach(function (h, i) {
            const opt = document.createElement('option');
            opt.value = i; opt.textContent = h;
            if (columnMap[f] === i) opt.selected = true;
            sel.appendChild(opt);
        });

        sel.addEventListener('change', function () {
            columnMap[f] = parseInt(this.value);
            buildPreview();
        });

        wrap.appendChild(label);
        wrap.appendChild(sel);
        grid.appendChild(wrap);
    });
}

function buildPreview() {
    const mapped = FIELDS.filter(function (f) { return columnMap[f] >= 0; });
    document.querySelector('#preview-table thead').innerHTML = '<tr>' + mapped.map(function (f) {
        return '<th style="padding:.4rem .6rem;color:#64748b;font-size:.72rem;text-transform:uppercase;letter-spacing:.4px">' + FIELD_LABELS[f] + '</th>';
    }).join('') + '</tr>';
    document.querySelector('#preview-table tbody').innerHTML = parsedRows.slice(0, 10).map(function (row) {
        return '<tr>' + mapped.map(function (f) {
            return '<td style="padding:.35rem .6rem;color:#374151;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + (row[columnMap[f]] ?? '') + '</td>';
        }).join('') + '</tr>';
    }).join('');
}

function resetImport() {
    parsedRows = []; fileHeaders = []; columnMap = {};
    document.getElementById('import-step1').style.display = '';
    document.getElementById('import-step2').style.display = 'none';
    document.getElementById('importBtn').style.display = 'none';
    document.getElementById('fileInput').value = '';
    document.getElementById('import-error').style.display = 'none';
}

document.getElementById('changeFileBtn').addEventListener('click', resetImport);

// ── Do import ─────────────────────────────────────────────────
document.getElementById('importBtn').addEventListener('click', function () {
    const rows = parsedRows.map(function (row) {
        const obj = {};
        FIELDS.forEach(function (f) {
            if (columnMap[f] >= 0) {
                let v = row[columnMap[f]];
                if (v === '' || v === null || v === undefined) v = null;
                obj[f] = v;
            }
        });
        return obj;
    }).filter(function (r) { return Object.values(r).some(function (v) { return v !== null && v !== ''; }); });

    if (!rows.length) { showImportError('No valid rows.'); return; }

    const lbl = document.getElementById('importBtnLabel');
    lbl.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Importing…';
    document.getElementById('importBtn').disabled = true;

    fetch(IMPORT_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
        body: JSON.stringify({ rows }),
    }).then(function (r) { return r.json(); }).then(function (data) {
        if (data.imported) {
            bootstrap.Modal.getInstance(document.getElementById('importModal')).hide();
            resetImport();
            location.reload();
        } else {
            showImportError(JSON.stringify(data));
        }
    }).catch(function (err) {
        showImportError('Import failed: ' + err.message);
        lbl.innerHTML = '<i class="bi bi-upload me-1"></i> Import';
        document.getElementById('importBtn').disabled = false;
    });
});

function showImportError(msg) {
    const el = document.getElementById('import-error');
    el.textContent = msg;
    el.style.display = '';
}

}); // DOMContentLoaded
</script>
@endpush
