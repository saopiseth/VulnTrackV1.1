@extends('layouts.app')
@section('title', 'New VA Assessment')

@section('content')
<style>
    .form-card { background:#fff; border:1px solid #e8f5c2; border-radius:14px; padding:1.75rem; margin-bottom:1.25rem; }
    .form-card h6 { font-size:.8rem; font-weight:700; color:var(--primary-dark); text-transform:uppercase; letter-spacing:.8px; margin-bottom:1.25rem; padding-bottom:.6rem; border-bottom:2px solid var(--primary); }
    .form-label { font-size:.82rem; font-weight:600; color:#374151; margin-bottom:.35rem; }
    .form-control, .form-select { border-radius:9px; border:1.5px solid #e2e8f0; font-size:.875rem; }
    .form-control:focus, .form-select:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(var(--primary-rgb),.15); }
    .scope-card { cursor:pointer;display:block;border:1.5px solid #e2e8f0;border-radius:11px;padding:.75rem 1rem;transition:border-color .15s,background .15s }
    .scope-card:hover { border-color:var(--primary);background:rgb(248,253,235) }
    .scope-card.selected { border-color:var(--primary);background:rgb(248,253,235) }
    .host-th { padding:.42rem .55rem;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;white-space:nowrap }
    .host-td { padding:.48rem .55rem;font-size:.8rem;vertical-align:middle }
    #hosts-preview { display:none }
    .crit-badge { display:inline-block;border-radius:6px;padding:.1rem .45rem;font-size:.67rem;font-weight:700;white-space:nowrap }
    .scope-badge { display:inline-block;background:#f0fdf4;color:#166534;border-radius:6px;padding:.1rem .45rem;font-size:.67rem;font-weight:700 }
    .env-badge  { display:inline-block;background:#dbeafe;color:#1e40af;border-radius:6px;padding:.1rem .45rem;font-size:.67rem;font-weight:700 }
</style>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>New Assessment</h4>
        <p>Fill in the assessment details and upload scans after creation.</p>
    </div>
    <a href="{{ route('vuln-assessments.index') }}" class="btn btn-sm"
        style="border:1.5px solid var(--primary);border-radius:9px;color:var(--primary-dark);background:#fff;font-weight:500">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<form method="POST" action="{{ route('vuln-assessments.store') }}" id="assessment-form">
@csrf

{{-- ── Assessment Details ── --}}
<div class="form-card">
    <h6><i class="bi bi-info-circle me-2"></i>Assessment Details</h6>
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label">Assessment Name <span style="color:#dc2626">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name') }}" placeholder="e.g. Q2 2026 Infrastructure Vulnerability Assessment" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="4"
                placeholder="Brief description of scope, objectives, and systems in scope…">{{ old('description') }}</textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">Assessment Period — From</label>
            <input type="date" name="period_start" class="form-control @error('period_start') is-invalid @enderror"
                value="{{ old('period_start') }}">
            @error('period_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Assessment Period — To</label>
            <input type="date" name="period_end" class="form-control @error('period_end') is-invalid @enderror"
                value="{{ old('period_end') }}">
            @error('period_end')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

{{-- ── Assessment Scope ── --}}
<div class="form-card">
    <h6><i class="bi bi-diagram-3-fill me-2"></i>Assessment Scope
        <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#94a3b8;font-size:.75rem;margin-left:.5rem">
            Select a scope group — hosts will be previewed below
        </span>
    </h6>

    @if($scopeGroups->isEmpty())
        <div class="text-center py-4" style="color:#94a3b8">
            <i class="bi bi-diagram-3" style="font-size:1.75rem;display:block;margin-bottom:.5rem"></i>
            No scope groups defined yet.
            <a href="{{ route('assessment-scope.index') }}" style="color:var(--primary-dark)">Create a scope group</a> first.
        </div>
    @else
        <div class="row g-2" id="scope-group-list">
            @foreach($scopeGroups as $group)
            <div class="col-md-6">
                <label class="scope-card {{ old('scope_group_id') == $group->id ? 'selected' : '' }}">
                    <div class="d-flex align-items-center gap-3">
                        <input type="radio" name="scope_group_id" value="{{ $group->id }}"
                               class="scope-radio form-check-input mt-0"
                               style="width:1rem;height:1rem;flex-shrink:0;accent-color:var(--primary)"
                               {{ old('scope_group_id') == $group->id ? 'checked' : '' }}>
                        <div style="flex:1;min-width:0">
                            <div style="font-weight:600;color:#0f172a;font-size:.875rem">{{ $group->name }}</div>
                            <div style="font-size:.78rem;color:#64748b;margin-top:.15rem">
                                <i class="bi bi-hdd-stack me-1"></i>
                                <span class="asset-count-{{ $group->id }}">{{ $group->items_count }}</span>
                                asset{{ $group->items_count == 1 ? '' : 's' }}
                                @if($group->description)
                                &nbsp;·&nbsp; {{ Str::limit($group->description, 50) }}
                                @endif
                            </div>
                        </div>
                        <div style="flex-shrink:0">
                            <span style="display:inline-block;background:var(--lime-muted,#f0fdf0);color:var(--primary-dark);
                                         border-radius:20px;padding:.1rem .6rem;font-size:.7rem;font-weight:700">
                                {{ $group->items_count }}
                            </span>
                        </div>
                    </div>
                </label>
            </div>
            @endforeach
        </div>

        <div class="mt-2">
            <label style="cursor:pointer;display:inline-flex;align-items:center;gap:.5rem;font-size:.82rem;color:#64748b">
                <input type="radio" name="scope_group_id" value="" class="scope-radio form-check-input mt-0"
                       style="width:.9rem;height:.9rem"
                       {{ !old('scope_group_id') ? 'checked' : '' }}>
                No scope selected
            </label>
        </div>
    @endif
</div>

{{-- ── Vulnerable Hosts Preview ── --}}
<div class="form-card" id="hosts-preview">
    <h6>
        <i class="bi bi-hdd-network me-2"></i>Vulnerable Hosts
        <span id="hosts-count-badge" style="font-weight:600;text-transform:none;letter-spacing:0;
              background:var(--primary);color:#fff;border-radius:20px;padding:.1rem .65rem;
              font-size:.72rem;margin-left:.5rem">0</span>
        <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#94a3b8;font-size:.75rem;margin-left:.5rem">
            auto-populated from selected scope group
        </span>
    </h6>

    {{-- Loading state --}}
    <div id="hosts-loading" style="display:none;text-align:center;padding:2rem;color:#94a3b8">
        <div class="spinner-border spinner-border-sm me-2" style="color:var(--primary)" role="status"></div>
        Loading scope entries…
    </div>

    {{-- Empty state --}}
    <div id="hosts-empty" style="display:none;text-align:center;padding:2rem;color:#94a3b8">
        <i class="bi bi-inbox" style="font-size:1.6rem;display:block;margin-bottom:.5rem;opacity:.4"></i>
        No entries found in this scope group.
        <a href="{{ route('assessment-scope.index') }}" style="color:var(--primary-dark)" target="_blank">Add entries</a>
    </div>

    {{-- Hosts table --}}
    <div id="hosts-table-wrap" style="display:none;overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:.8rem">
            <thead>
                <tr style="border-bottom:2px solid #e8f5c2">
                    <th class="host-th">#</th>
                    <th class="host-th">IP Address</th>
                    <th class="host-th">Hostname</th>
                    <th class="host-th">System</th>
                    <th class="host-th">Criticality</th>
                    <th class="host-th">Owner</th>
                    <th class="host-th">Scope</th>
                    <th class="host-th">Environment</th>
                </tr>
            </thead>
            <tbody id="hosts-tbody"></tbody>
        </table>
    </div>

    {{-- Validation message --}}
    <div id="hosts-required-msg" style="display:none;margin-top:.75rem;padding:.55rem .9rem;
         background:#fef2f2;border:1px solid #fecaca;border-radius:8px;color:#dc2626;font-size:.82rem">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>
        Please select a scope group with at least one host before creating the assessment.
    </div>
</div>

{{-- ── SLA Policy ── --}}
@if($slaPolicies->isNotEmpty())
<div class="form-card">
    <h6><i class="bi bi-stopwatch-fill me-2"></i>SLA Policy</h6>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Remediation SLA</label>
            <select name="sla_policy_id" class="form-select" style="border-radius:9px;font-size:.875rem">
                <option value="">— Use default or none —</option>
                @foreach($slaPolicies as $sp)
                <option value="{{ $sp->id }}" {{ old('sla_policy_id') == $sp->id ? 'selected' : '' }}>
                    {{ $sp->name }}{{ $sp->is_default ? ' (Default)' : '' }}
                </option>
                @endforeach
            </select>
            <div style="font-size:.76rem;color:#94a3b8;margin-top:.35rem">
                Sets remediation deadlines per severity on the findings page.
            </div>
        </div>
    </div>
</div>
@endif

{{-- ── Actions ── --}}
<div class="d-flex justify-content-end gap-2">
    <a href="{{ route('vuln-assessments.index') }}" class="btn btn-sm"
        style="border:1.5px solid #cbd5e1;border-radius:9px;color:#64748b;background:#fff;font-weight:500;padding:.45rem 1.2rem">
        Cancel
    </a>
    <button type="submit" id="submit-btn" class="btn btn-sm"
        style="background:var(--primary);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.45rem 1.4rem">
        <i class="bi bi-plus-lg me-1"></i> Create Assessment
    </button>
</div>
</form>
@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
(function () {
    // ── Route template (replace placeholder with group id at runtime) ──────
    const ITEMS_URL = '{{ route('assessment-scope.items.json', ['assessmentScopeGroup' => '__ID__']) }}';

    // ── DOM refs ────────────────────────────────────────────────────────────
    const preview      = document.getElementById('hosts-preview');
    const loading      = document.getElementById('hosts-loading');
    const empty        = document.getElementById('hosts-empty');
    const tableWrap    = document.getElementById('hosts-table-wrap');
    const tbody        = document.getElementById('hosts-tbody');
    const countBadge   = document.getElementById('hosts-count-badge');
    const requiredMsg  = document.getElementById('hosts-required-msg');
    const form         = document.getElementById('assessment-form');

    let currentGroupId = null;
    let currentItems   = [];

    // ── Highlight selected scope card ───────────────────────────────────────
    function syncCardStyles() {
        document.querySelectorAll('.scope-radio').forEach(radio => {
            const card = radio.closest('.scope-card');
            if (!card) return;
            card.classList.toggle('selected', radio.checked && radio.value !== '');
        });
    }

    // ── Fetch and render scope items ────────────────────────────────────────
    async function loadGroup(groupId) {
        if (groupId === currentGroupId) return;
        currentGroupId = groupId;

        preview.style.display = '';
        loading.style.display = '';
        empty.style.display   = 'none';
        tableWrap.style.display = 'none';
        requiredMsg.style.display = 'none';
        tbody.innerHTML = '';
        countBadge.textContent = '0';

        try {
            const url  = ITEMS_URL.replace('__ID__', groupId);
            const resp = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!resp.ok) throw new Error('HTTP ' + resp.status);
            currentItems = await resp.json();
        } catch (e) {
            loading.style.display = 'none';
            empty.style.display   = '';
            empty.querySelector('span') && (empty.querySelector('span').textContent = 'Failed to load entries.');
            return;
        }

        loading.style.display = 'none';

        if (!currentItems.length) {
            empty.style.display = '';
            return;
        }

        countBadge.textContent = currentItems.length;
        currentItems.forEach((item, i) => {
            tbody.insertAdjacentHTML('beforeend', buildRow(item, i + 1));
        });
        tableWrap.style.display = '';
    }

    function hidePreview() {
        currentGroupId = null;
        currentItems   = [];
        preview.style.display = 'none';
        tbody.innerHTML = '';
        countBadge.textContent = '0';
        requiredMsg.style.display = 'none';
    }

    // ── Row builder ─────────────────────────────────────────────────────────
    function buildRow(item, n) {
        const ip       = item.ip_address   || '<span style="color:#cbd5e1">—</span>';
        const host     = item.hostname     || '<span style="color:#cbd5e1">—</span>';
        const system   = item.system_name  || '<span style="color:#cbd5e1">—</span>';
        const owner    = item.system_owner || '<span style="color:#cbd5e1">—</span>';

        let critHtml = '<span style="color:#cbd5e1">—</span>';
        if (item.criticality_label) {
            critHtml = `<span class="crit-badge" style="background:${item.criticality_bg};color:${item.criticality_color}">${item.criticality_label}</span>`;
        }

        let scopeHtml = '<span style="color:#cbd5e1">—</span>';
        if (item.identified_scope) {
            scopeHtml = `<span class="scope-badge">${item.identified_scope}</span>`;
        }

        let envHtml = '<span style="color:#cbd5e1">—</span>';
        if (item.environment) {
            envHtml = `<span class="env-badge">${item.environment}</span>`;
        }

        const rowBg = n % 2 === 0 ? '#fafafa' : '';
        return `<tr style="border-bottom:1px solid #f1f5f9;background:${rowBg}"
                    onmouseover="this.style.background='#f8fce8'" onmouseout="this.style.background='${rowBg}'">
            <td class="host-td" style="color:#cbd5e1;font-size:.71rem;font-weight:600">${n}</td>
            <td class="host-td"><span style="font-family:monospace;font-weight:700;color:#0f172a;font-size:.82rem">${ip}</span></td>
            <td class="host-td" style="color:#475569;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${host}</td>
            <td class="host-td" style="color:#374151;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${system}</td>
            <td class="host-td">${critHtml}</td>
            <td class="host-td" style="color:#475569;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${owner}</td>
            <td class="host-td">${scopeHtml}</td>
            <td class="host-td">${envHtml}</td>
        </tr>`;
    }

    // ── Wire up radio buttons ───────────────────────────────────────────────
    document.querySelectorAll('.scope-radio').forEach(radio => {
        radio.addEventListener('change', function () {
            syncCardStyles();
            if (this.value) {
                loadGroup(this.value);
            } else {
                hidePreview();
            }
        });
    });

    // ── Form submit validation ──────────────────────────────────────────────
    form.addEventListener('submit', function (e) {
        const checked = document.querySelector('.scope-radio:checked');
        // If a group is selected but it returned 0 items, warn (soft — don't block)
        if (checked && checked.value && currentItems.length === 0 && preview.style.display !== 'none') {
            requiredMsg.style.display = '';
            requiredMsg.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    });

    // ── Restore state on page load (validation error repopulate) ───────────
    document.addEventListener('DOMContentLoaded', function () {
        syncCardStyles();
        const checked = document.querySelector('.scope-radio:checked');
        if (checked && checked.value) {
            loadGroup(checked.value);
        }
    });
})();
</script>
@endpush
