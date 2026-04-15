@extends('layouts.app')
@section('title', 'OS Assets — Inventory')

@section('content')
<style>
    :root { --lime: rgb(152,194,10); --lime-dark: rgb(118,151,7); --lime-light: rgb(240,248,210); --lime-muted: rgb(232,244,195); }
    .va-card { background:#fff; border:1px solid #e8f5c2; border-radius:14px; padding:1.25rem 1.5rem; margin-bottom:1.25rem; }
    .va-card h6 { font-size:.78rem; font-weight:700; color:var(--lime-dark); text-transform:uppercase; letter-spacing:.8px; margin-bottom:.9rem; padding-bottom:.5rem; border-bottom:2px solid var(--lime); }
    .os-badge { display:inline-flex; align-items:center; gap:.3rem; padding:.18rem .55rem; border-radius:20px; font-size:.72rem; font-weight:600; }
    .override-badge { background:#fef9c3; color:#854d0e; padding:.12rem .45rem; border-radius:10px; font-size:.68rem; font-weight:700; }
    .fam-btn { padding:.28rem .8rem; border-radius:20px; font-size:.78rem; font-weight:600; cursor:pointer; border:1.5px solid transparent; text-decoration:none; display:inline-block; }
    .fam-btn.active { border-color:var(--lime-dark); background:var(--lime-light); color:var(--lime-dark); }
    .fam-btn:not(.active) { background:#f8fafc; color:#64748b; border-color:#e2e8f0; }
    thead.lime-head th { background:var(--lime-muted) !important; color:var(--lime-dark) !important; font-size:.75rem; }
    .icon-family { font-size:.9rem; }
    .crit-option { transition: border-color .15s, background .15s; }
    .crit-option:hover { border-color:#94a3b8 !important; background:#f8fafc !important; }
    #osTableBody { transition: opacity .15s; }
    #osTableBody.loading { opacity:.35; pointer-events:none; }
</style>

@php
    $familyMeta = [
        'Windows' => ['icon' => 'bi-windows',       'bg' => '#dbeafe', 'color' => '#1e40af', 'label' => 'Windows'],
        'Linux'   => ['icon' => 'bi-ubuntu',        'bg' => '#d1fae5', 'color' => '#065f46', 'label' => 'Linux'],
        'Unix'    => ['icon' => 'bi-terminal-fill', 'bg' => '#ffedd5', 'color' => '#7c2d12', 'label' => 'Unix-based'],
        'Other'   => ['icon' => 'bi-cpu-fill',      'bg' => '#f3f4f6', 'color' => '#374151', 'label' => 'Other'],
    ];
@endphp

{{-- Header --}}
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h4 style="margin-bottom:.2rem">OS Assets</h4>
        <div style="font-size:.84rem;color:#64748b">
            <i class="bi bi-cpu me-1"></i>All assessments
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('inventory.index') }}" class="btn btn-sm"
            style="border:1.5px solid rgb(152,194,10);border-radius:9px;color:rgb(118,151,7);background:#fff;font-weight:500">
            <i class="bi bi-arrow-left me-1"></i> Back to Inventory
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" style="border-radius:10px;font-size:.875rem">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- OS Distribution Cards --}}
@if($osDistribution->count())
<div class="row g-2 mb-3">
    @php $totalHosts = $osDistribution->sum('cnt'); @endphp
    <div class="col-md-2 d-flex">
        <div style="background:#fff;border:1px solid #e8f5c2;border-radius:12px;padding:.9rem 1rem;
                    text-align:center;width:100%;display:flex;flex-direction:column;justify-content:center">
            <div style="font-size:.68rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Total Hosts</div>
            <div style="font-size:1.6rem;font-weight:800;color:#0f172a;line-height:1.1">{{ $totalHosts }}</div>
            <div style="font-size:.7rem;color:#cbd5e1;margin-top:.15rem">across all families</div>
        </div>
    </div>
    @foreach($osDistribution as $dist)
    @php $meta = $familyMeta[$dist->family] ?? $familyMeta['Other']; $pct = $totalHosts > 0 ? round($dist->cnt / $totalHosts * 100) : 0; @endphp
    <div class="col-md d-flex">
        <div style="background:{{ $meta['bg'] }};border:1px solid {{ $meta['bg'] }};border-radius:12px;padding:.9rem 1rem;width:100%">
            <div style="font-size:.68rem;color:{{ $meta['color'] }};font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.25rem">
                <i class="bi {{ $meta['icon'] }} me-1"></i>{{ $meta['label'] }}
            </div>
            <div style="font-size:1.5rem;font-weight:800;color:{{ $meta['color'] }}">{{ $dist->cnt }}</div>
            <div style="font-size:.7rem;color:{{ $meta['color'] }};opacity:.8">{{ $pct }}% of hosts</div>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Filters --}}
<div class="va-card" style="padding:.9rem 1.25rem;margin-bottom:1rem">
    <div class="d-flex gap-2 flex-wrap align-items-center">
        {{-- Family tabs --}}
        <div class="d-flex gap-1 flex-wrap" id="famTabBar">
            <a href="#" class="fam-btn {{ !request('family') ? 'active' : '' }}" data-family="">All</a>
            @foreach(['Windows','Linux','Unix','Other'] as $fam)
            <a href="#" class="fam-btn {{ request('family') === $fam ? 'active' : '' }}" data-family="{{ $fam }}">
                <i class="bi {{ $familyMeta[$fam]['icon'] ?? 'bi-cpu' }} me-1"></i>{{ $fam }}
            </a>
            @endforeach
        </div>
        {{-- Search --}}
        <div class="ms-auto d-flex gap-2">
            <input type="text" id="osSearchInput" class="form-control form-control-sm"
                placeholder="Search IP, hostname, OS…"
                value="{{ request('search') }}"
                style="border-radius:8px;width:220px;font-size:.82rem">
            <a href="#" id="osClearFilters"
               class="btn btn-sm {{ !request()->hasAny(['family','search']) ? 'd-none' : '' }}"
               style="border:1.5px solid #cbd5e1;border-radius:8px;color:#64748b;background:#fff">
                <i class="bi bi-x"></i>
            </a>
        </div>
    </div>
</div>

{{-- Host OS Table --}}
<div class="va-card" style="padding:0;overflow:hidden">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.82rem">
            <thead class="lime-head">
                <tr>
                    <th style="padding:.7rem 1rem;width:130px">IP Address</th>
                    <th style="padding:.7rem .75rem">Hostname</th>
                    <th style="padding:.7rem .75rem">Detected OS</th>
                    <th style="padding:.7rem .75rem;width:90px">Family</th>
                    <th style="padding:.7rem .75rem">Kernel / Build</th>
                    <th style="padding:.7rem .75rem;width:150px">Actions</th>
                </tr>
            </thead>
            <tbody id="osTableBody">
                @include('inventory._os_rows', ['hosts' => $hosts, 'familyMeta' => $familyMeta])
            </tbody>
        </table>
    </div>
</div>

{{-- Pagination --}}
<div id="osPaginationContainer" class="{{ $hosts->hasPages() ? 'd-flex justify-content-center mt-2' : '' }}">
    {{ $hosts->links() }}
</div>

{{-- ══ Shared: OS Override Modal ══ --}}
<div class="modal fade" id="sharedOverrideModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content" style="border-radius:14px;border:1px solid #e8f5c2">
            <div class="modal-header" style="border-bottom:2px solid rgb(152,194,10);padding:.85rem 1.25rem">
                <h6 class="modal-title" id="om-title" style="font-size:.9rem;font-weight:700">
                    <i class="bi bi-pencil me-1" style="color:rgb(152,194,10)"></i>OS Override
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="overrideForm" action="">
                @csrf
                <div class="modal-body" style="padding:1.1rem 1.25rem">
                    <div id="om-autodetect" style="font-size:.75rem;color:#94a3b8;margin-bottom:.75rem"></div>
                    <div class="mb-2">
                        <label class="form-label" style="font-size:.78rem;font-weight:600;color:#374151">Override OS Name</label>
                        <input type="text" name="os_override" id="om-os-name" class="form-control form-control-sm"
                            placeholder="e.g. Ubuntu 22.04 LTS" style="border-radius:7px;font-size:.82rem">
                    </div>
                    <div class="mb-2">
                        <label class="form-label" style="font-size:.78rem;font-weight:600;color:#374151">OS Family</label>
                        <select name="os_override_family" id="om-os-family" class="form-select form-select-sm" style="border-radius:7px;font-size:.82rem">
                            <option value="">— Keep auto-detected —</option>
                            @foreach(['Windows','Linux','Unix','Other'] as $fam)
                            <option value="{{ $fam }}">{{ $fam }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label" style="font-size:.78rem;font-weight:600;color:#374151">Note</label>
                        <input type="text" name="os_override_note" id="om-note" class="form-control form-control-sm"
                            placeholder="Reason for override…" style="border-radius:7px;font-size:.82rem">
                    </div>
                    <div id="om-clear-hint" style="font-size:.72rem;color:#94a3b8;margin-top:.5rem;display:none">
                        Leave OS Name blank to clear the override.
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #e8f5c2;padding:.6rem 1.25rem">
                    <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
                        style="border:1.5px solid #cbd5e1;border-radius:7px;color:#64748b;background:#fff">Cancel</button>
                    <button type="submit" class="btn btn-sm"
                        style="background:rgb(152,194,10);color:#fff;border-radius:7px;font-weight:600;border:none">
                        Save Override
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══ Shared: Classification Modal ══ --}}
<div class="modal fade" id="sharedCritModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content" style="border-radius:14px;border:1px solid #e8f5c2">
            <div class="modal-header" style="border-bottom:2px solid rgb(152,194,10);padding:.85rem 1.25rem">
                <h6 class="modal-title" id="cm-title" style="font-size:.9rem;font-weight:700">
                    <i class="bi bi-shield-check me-1" style="color:rgb(152,194,10)"></i>Asset Criticality
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="critForm" action="">
                @csrf
                <div class="modal-body" style="padding:1.1rem 1.25rem">
                    <div id="cm-current" style="font-size:.75rem;color:#64748b;margin-bottom:.85rem;display:none"></div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label" style="font-size:.78rem;font-weight:600;color:#374151">
                                <i class="bi bi-hdd-stack me-1" style="color:#94a3b8"></i>System Name
                            </label>
                            <input type="text" name="system_name" id="cm-sysname" class="form-control form-control-sm"
                                placeholder="e.g. Core Banking DB" style="border-radius:7px;font-size:.82rem">
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-size:.78rem;font-weight:600;color:#374151">
                                <i class="bi bi-person me-1" style="color:#94a3b8"></i>System Owner
                            </label>
                            <input type="text" name="system_owner" id="cm-owner" class="form-control form-control-sm"
                                placeholder="e.g. John Smith" style="border-radius:7px;font-size:.82rem">
                        </div>
                    </div>
                    <div style="font-size:.73rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.5rem">Asset Criticality</div>
                    <div style="display:flex;flex-direction:column;gap:.5rem" id="cm-options">
                        @foreach(\App\Models\VulnHostOs::criticalityLevels() as $level => $meta)
                        <label class="crit-option" data-level="{{ $level }}"
                            style="display:flex;align-items:flex-start;gap:.75rem;padding:.65rem .9rem;
                                border-radius:10px;border:1.5px solid #e2e8f0;background:#fafafa;cursor:pointer">
                            <input type="radio" name="asset_criticality" value="{{ $level }}"
                                style="margin-top:.2rem;accent-color:{{ $meta['color'] }}">
                            <div>
                                <div style="font-size:.82rem;font-weight:700;color:{{ $meta['color'] }}">
                                    <i class="bi {{ $meta['icon'] }} me-1"></i>{{ $level }}. {{ $meta['label'] }}
                                </div>
                                <div style="font-size:.73rem;color:#64748b;margin-top:.1rem">{{ $meta['desc'] }}</div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #e8f5c2;padding:.6rem 1.25rem">
                    <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
                        style="border:1.5px solid #cbd5e1;border-radius:7px;color:#64748b;background:#fff">Cancel</button>
                    <button type="submit" class="btn btn-sm"
                        style="background:rgb(152,194,10);color:#fff;border-radius:7px;font-weight:600;border:none">
                        Save Classification
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══ Shared: OS History Modal ══ --}}
<div class="modal fade" id="sharedHistoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:14px;border:1px solid #e8f5c2">
            <div class="modal-header" style="border-bottom:2px solid rgb(152,194,10);padding:.85rem 1.25rem">
                <h6 class="modal-title" id="hm-title" style="font-size:.9rem;font-weight:700">
                    <i class="bi bi-clock-history me-1" style="color:rgb(152,194,10)"></i>OS History
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:1.1rem 1.25rem" id="hm-body"></div>
            <div class="modal-footer" style="border-top:1px solid #e8f5c2;padding:.6rem 1.25rem">
                <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
                    style="border:1.5px solid #cbd5e1;border-radius:7px;color:#64748b;background:#fff">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const overrideUrl = '{{ url("/inventory/os-override") }}';
    const critUrl     = '{{ url("/inventory/os-assets") }}';

    const filters = {
        family: '{{ request("family") }}',
        search: '{{ request("search") }}',
        page:   1,
    };

    let searchTimer = null;
    const tbody   = document.getElementById('osTableBody');
    const pagCont = document.getElementById('osPaginationContainer');
    const clearBtn= document.getElementById('osClearFilters');

    function hasFilters() { return !!(filters.family || filters.search); }

    function setLoading(on) { tbody.classList.toggle('loading', on); }

    // ── Fetch rows via AJAX ───────────────────────────────────────────────────
    function fetchResults(resetPage = true) {
        if (resetPage) filters.page = 1;
        const params = new URLSearchParams();
        Object.entries(filters).forEach(([k, v]) => { if (v) params.set(k, v); });

        setLoading(true);

        fetch('{{ route("inventory.os-assets") }}?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(r => r.json())
        .then(data => {
            tbody.innerHTML = data.html;
            pagCont.innerHTML = data.links;
            pagCont.className = data.total > 30 ? 'd-flex justify-content-center mt-2' : '';
            attachRowListeners();
            attachPaginationHandlers();
            setLoading(false);
            clearBtn.classList.toggle('d-none', !hasFilters());
            history.replaceState(null, '',
                '{{ route("inventory.os-assets") }}?' + params.toString());
        })
        .catch(() => setLoading(false));
    }

    // ── Family tabs ───────────────────────────────────────────────────────────
    document.getElementById('famTabBar').addEventListener('click', function (e) {
        const btn = e.target.closest('.fam-btn');
        if (!btn) return;
        e.preventDefault();
        filters.family = btn.dataset.family;
        this.querySelectorAll('.fam-btn')
            .forEach(b => b.classList.toggle('active', b.dataset.family === filters.family));
        fetchResults();
    });

    // ── Debounced search ──────────────────────────────────────────────────────
    document.getElementById('osSearchInput').addEventListener('input', function () {
        clearTimeout(searchTimer);
        filters.search = this.value;
        searchTimer = setTimeout(() => fetchResults(), 350);
    });

    // ── Clear ─────────────────────────────────────────────────────────────────
    clearBtn.addEventListener('click', function (e) {
        e.preventDefault();
        filters.family = filters.search = '';
        filters.page   = 1;
        document.getElementById('osSearchInput').value = '';
        document.querySelectorAll('#famTabBar .fam-btn')
            .forEach(b => b.classList.toggle('active', b.dataset.family === ''));
        fetchResults();
    });

    // ── Pagination ────────────────────────────────────────────────────────────
    function attachPaginationHandlers() {
        pagCont.querySelectorAll('a[href]').forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                try { filters.page = new URL(link.href).searchParams.get('page') || 1; }
                catch { filters.page = 1; }
                fetchResults(false);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    }
    attachPaginationHandlers();

    // ── Bootstrap modal instances ─────────────────────────────────────────────
    const overrideModal = new bootstrap.Modal(document.getElementById('sharedOverrideModal'));
    const critModal     = new bootstrap.Modal(document.getElementById('sharedCritModal'));
    const histModal     = new bootstrap.Modal(document.getElementById('sharedHistoryModal'));

    const CRIT = @json(\App\Models\VulnHostOs::criticalityLevels());

    // ── Row listeners ─────────────────────────────────────────────────────────
    function attachRowListeners() {
        tbody.querySelectorAll('tr[data-host]').forEach(row => {
            const host = JSON.parse(row.dataset.host);
            row.querySelector('.os-override-btn')?.addEventListener('click', () => openOverride(host));
            row.querySelector('.os-crit-btn')    ?.addEventListener('click', () => openCrit(host));
            row.querySelector('.os-history-btn') ?.addEventListener('click', () => openHistory(host));
        });
    }
    attachRowListeners();

    // ── Override modal ────────────────────────────────────────────────────────
    function openOverride(h) {
        document.getElementById('om-title').innerHTML =
            `<i class="bi bi-pencil me-1" style="color:rgb(152,194,10)"></i>OS Override — ${h.ip}`;
        document.getElementById('om-autodetect').textContent =
            `Auto-detected: ${h.os_name || 'None'} (${h.os_confidence}% confidence)`;
        document.getElementById('om-os-name').value   = h.os_override || '';
        document.getElementById('om-note').value      = h.os_override_note || '';
        document.getElementById('om-clear-hint').style.display = h.has_override ? '' : 'none';

        const famSel = document.getElementById('om-os-family');
        famSel.value = h.os_override_fam || '';

        document.getElementById('overrideForm').action = `${overrideUrl}/${h.id}`;
        overrideModal.show();
    }

    // ── Criticality modal ─────────────────────────────────────────────────────
    function openCrit(h) {
        document.getElementById('cm-title').innerHTML =
            `<i class="bi bi-shield-check me-1" style="color:rgb(152,194,10)"></i>Asset Criticality — ${h.ip}`;

        const curDiv = document.getElementById('cm-current');
        if (h.criticality) {
            const cm = CRIT[h.criticality];
            curDiv.style.display = '';
            curDiv.innerHTML = `Current: <strong>${h.criticality}. ${cm ? cm.label : ''}</strong>`
                + (h.crit_at ? ` &middot; set ${h.crit_at}` : '');
        } else {
            curDiv.style.display = 'none';
        }

        document.getElementById('cm-sysname').value = h.system_name || '';
        document.getElementById('cm-owner').value   = h.system_owner || '';

        document.getElementById('cm-options').querySelectorAll('.crit-option').forEach(opt => {
            const lv = parseInt(opt.dataset.level);
            const cm = CRIT[lv] || {};
            const isCur = lv === h.criticality;
            opt.querySelector('input[type=radio]').checked = isCur;
            opt.style.borderColor = isCur ? cm.color : '#e2e8f0';
            opt.style.background  = isCur ? cm.bg    : '#fafafa';
        });

        document.getElementById('critForm').action = `${critUrl}/${h.id}/criticality`;
        critModal.show();
    }

    // ── History modal ─────────────────────────────────────────────────────────
    function openHistory(h) {
        document.getElementById('hm-title').innerHTML =
            `<i class="bi bi-clock-history me-1" style="color:rgb(152,194,10)"></i>OS History — ${h.ip}`;

        let html = '';
        const hist = Array.isArray(h.os_history) ? [...h.os_history].reverse() : [];
        hist.forEach(entry => {
            html += `<div style="border:1px solid #f1f5f9;border-radius:9px;padding:.65rem .9rem;margin-bottom:.5rem">
                <div style="font-weight:600;color:#0f172a;font-size:.84rem">${entry.os_name || 'Unknown'}</div>
                <div style="font-size:.72rem;color:#64748b">
                    Family: ${entry.os_family || '—'} &middot;
                    Confidence: ${entry.confidence || 0}%
                    ${entry.detected_at ? '&middot; ' + entry.detected_at : ''}
                </div></div>`;
        });

        const effectiveOs = h.os_override || h.os_name || 'Unknown';
        html += `<div style="border:1.5px solid rgb(152,194,10);border-radius:9px;padding:.65rem .9rem;background:rgb(240,248,210)">
            <div style="font-size:.65rem;color:rgb(118,151,7);font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.25rem">Current</div>
            <div style="font-weight:600;color:#0f172a;font-size:.84rem">${effectiveOs}</div>
            <div style="font-size:.72rem;color:#64748b">
                Family: ${h.os_family}
                ${h.has_override ? ' &middot; <span style="background:#fef9c3;color:#854d0e;padding:.12rem .45rem;border-radius:10px;font-size:.68rem;font-weight:700">Manual Override</span>' : ''}
            </div></div>`;

        document.getElementById('hm-body').innerHTML = html;
        histModal.show();
    }

})();
</script>
@endpush

@endsection
