@extends('layouts.app')
@section('title', 'Assessment Scope')

@section('content')

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
        <h4 class="mb-0">Assessment Scope</h4>
        <p class="mb-0">Named scope groups — each group holds the in-scope assets for an assessment</p>
    </div>
    <div class="d-flex gap-2">
        @if(Auth::user()->isAdministrator())
        <button class="btn js-open-criticality"
                style="border:1.5px solid var(--primary);color:var(--primary-dark);background:#fff;border-radius:10px;font-size:.875rem;font-weight:600">
            <i class="bi bi-bar-chart-steps me-1"></i> Criticality Labels
        </button>
        @endif
        <button class="btn btn-primary js-open-create"
                style="background:var(--primary);border-color:var(--primary);border-radius:10px;font-size:.875rem;font-weight:600">
            <i class="bi bi-plus-lg me-1"></i> New Scope Group
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert d-flex align-items-center gap-2 mb-4" style="border-radius:12px;border:none;background:#f0fdf4;color:#166534;">
    <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
</div>
@endif

@error('criticality')
<div class="alert d-flex align-items-center gap-2 mb-4" style="border-radius:12px;border:none;background:#fef2f2;color:#991b1b;">
    <i class="bi bi-exclamation-circle-fill"></i> {{ $message }}
</div>
@enderror

@if($groups->isEmpty())
<div class="card text-center py-5">
    <div style="color:#94a3b8">
        <i class="bi bi-diagram-3" style="font-size:2.5rem;display:block;margin-bottom:.75rem"></i>
        <div style="font-weight:600;color:#374151;margin-bottom:.35rem">No scope groups yet</div>
        <div style="font-size:.85rem;margin-bottom:1rem">Create a named scope group to start defining in-scope assets.</div>
        <button class="btn btn-primary js-open-create"
                style="background:var(--primary);border-color:var(--primary);border-radius:10px;font-size:.875rem;font-weight:600">
            <i class="bi bi-plus-lg me-1"></i> New Scope Group
        </button>
    </div>
</div>
@else
<div class="row g-3">
    @foreach($groups as $group)
    <div class="col-md-6 col-xl-4">
        <div class="card h-100" style="border-radius:14px;transition:box-shadow .2s" onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.08)'" onmouseout="this.style.boxShadow=''">
            <div class="card-body p-4">
                <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                    <div class="d-flex align-items-center gap-2" style="min-width:0">
                        <div style="width:40px;height:40px;border-radius:11px;background:rgb(232,244,195);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            <i class="bi bi-diagram-3-fill" style="color:var(--primary-dark)"></i>
                        </div>
                        <div style="min-width:0">
                            <a href="{{ route('assessment-scope.show', $group) }}"
                               style="font-weight:700;color:#0f172a;font-size:.95rem;text-decoration:none;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"
                               onmouseover="this.style.color='var(--primary-dark)'" onmouseout="this.style.color='#0f172a'">
                                {{ $group->name }}
                            </a>
                            <div style="font-size:.75rem;color:#94a3b8">{{ $group->created_at->format('d M Y') }}</div>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm" data-bs-toggle="dropdown"
                                style="border:1px solid #e2e8f0;border-radius:8px;color:#64748b;padding:.25rem .5rem;background:#fff">
                            <i class="bi bi-three-dots"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" style="border-radius:10px;border:1px solid #e2e8f0;font-size:.85rem">
                            <li>
                                <button class="dropdown-item js-open-edit"
                                        data-group='{{ json_encode(["id"=>$group->id,"name"=>$group->name,"description"=>$group->description]) }}'>
                                    <i class="bi bi-pencil me-2"></i>Edit
                                </button>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('assessment-scope.destroy', $group) }}"
                                      onsubmit="return confirm('Delete this scope group and all its entries?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-trash3 me-2"></i>Delete
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>

                @if($group->description)
                <p style="font-size:.82rem;color:#64748b;margin-bottom:.85rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">{{ $group->description }}</p>
                @endif

                <div class="d-flex align-items-center justify-content-between">
                    <span style="font-size:.8rem;color:#64748b">
                        <i class="bi bi-hdd-stack me-1"></i>
                        <strong style="color:#0f172a">{{ $group->items_count }}</strong> asset{{ $group->items_count == 1 ? '' : 's' }}
                    </span>
                    <a href="{{ route('assessment-scope.show', $group) }}" class="btn btn-sm"
                       style="background:rgb(232,244,195);color:var(--primary-dark);border:none;border-radius:8px;font-size:.78rem;font-weight:600">
                        View Assets <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif


{{-- ── Criticality Labels Modal (admin only) ── --}}
@if(Auth::user()->isAdministrator())
@php $critLevels = \App\Models\AssessmentScope::criticalityLevels(); @endphp
<div class="modal fade" id="criticalityModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:16px;border:none">
            <div class="modal-header px-4 pt-4 pb-3" style="border-bottom:1px solid #f1f5f9">
                <div>
                    <h5 class="modal-title mb-0" style="font-weight:700">
                        <i class="bi bi-bar-chart-steps me-2" style="color:var(--primary)"></i>System Criticality Labels
                    </h5>
                    <p class="mb-0 mt-1" style="font-size:.8rem;color:#64748b">
                        Add, edit, or remove criticality levels. Level numbers and names must each be unique.
                    </p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('account.criticality-settings.update') }}" id="criticalityForm">
                @csrf @method('PATCH')
                <div class="modal-body px-4 py-4">
                    <div id="crit-error" class="d-none mb-3"
                         style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:10px;font-size:.85rem;padding:.65rem 1rem">
                    </div>
                    <div style="overflow-y:auto;max-height:380px">
                        <table class="w-100" style="border-collapse:separate;border-spacing:0 .35rem">
                            <thead style="position:sticky;top:0;background:#fff;z-index:1">
                                <tr style="font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px">
                                    <th style="padding:.3rem .5rem;width:110px">Level</th>
                                    <th style="padding:.3rem .5rem">Criticality Name</th>
                                    <th style="width:46px"></th>
                                </tr>
                            </thead>
                            <tbody id="crit-rows">
                                @foreach($critLevels as $k => $lv)
                                <tr class="crit-row">
                                    <td style="padding:.3rem .4rem">
                                        <input type="number" class="form-control form-control-sm crit-level-input"
                                               value="{{ $k }}" min="1" max="99" required
                                               style="border-radius:8px;border-color:#e2e8f0;font-size:.875rem;width:85px">
                                    </td>
                                    <td style="padding:.3rem .4rem">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="crit-badge"
                                                  style="background:{{ $lv['bg'] }};color:{{ $lv['color'] }};padding:.15rem .5rem;border-radius:6px;font-size:.7rem;font-weight:700;white-space:nowrap;flex-shrink:0">
                                                {{ $lv['label'] }}
                                            </span>
                                            <input type="text" class="form-control form-control-sm crit-label-input"
                                                   value="{{ $lv['label'] }}" maxlength="60" required
                                                   placeholder="e.g. Critical"
                                                   style="border-radius:8px;border-color:#e2e8f0;font-size:.875rem">
                                        </div>
                                    </td>
                                    <td style="padding:.3rem .4rem;text-align:center">
                                        <button type="button" class="btn btn-sm js-remove-crit-row"
                                                style="border:1px solid #fecaca;color:#dc2626;background:#fff8f8;border-radius:8px;padding:.2rem .45rem">
                                            <i class="bi bi-trash3" style="font-size:.8rem"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button type="button" id="add-crit-row"
                            style="margin-top:.6rem;border:1.5px dashed #cbd5e1;background:#f8fafc;color:#64748b;border-radius:9px;font-size:.82rem;font-weight:600;padding:.4rem 1rem;width:100%;cursor:pointer">
                        <i class="bi bi-plus-lg me-1"></i> Add Level
                    </button>
                </div>
                <div class="modal-footer px-4 pb-4" style="border-top:1px solid #f1f5f9">
                    <button type="button" class="btn" data-bs-dismiss="modal"
                            style="border-color:#e2e8f0;color:#64748b;border-radius:10px;font-size:.875rem">Cancel</button>
                    <button type="submit" class="btn"
                            style="background:var(--primary);color:#fff;border-radius:10px;font-size:.875rem;font-weight:600">
                        <i class="bi bi-check-lg me-1"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- ── Create Group Modal ── --}}
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;border:none">
            <div class="modal-header px-4 pt-4 pb-3" style="border-bottom:1px solid #f1f5f9">
                <h5 class="modal-title" style="font-weight:700">New Scope Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('assessment-scope.store') }}">
                @csrf
                <div class="modal-body px-4 py-3">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.82rem">Group Name <span style="color:#dc2626">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" placeholder="e.g. Q2 2026 Infrastructure Scan"
                               style="border-radius:10px;border-color:#e2e8f0;font-size:.875rem" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label fw-semibold" style="font-size:.82rem">Description <span style="color:#94a3b8;font-weight:400">(optional)</span></label>
                        <textarea name="description" rows="3" class="form-control"
                                  placeholder="Brief description of this scope group…"
                                  style="border-radius:10px;border-color:#e2e8f0;font-size:.875rem;resize:none">{{ old('description') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer px-4 pb-4" style="border-top:1px solid #f1f5f9">
                    <button type="button" class="btn" data-bs-dismiss="modal"
                            style="border-color:#e2e8f0;color:#64748b;border-radius:10px;font-size:.875rem">Cancel</button>
                    <button type="submit" class="btn"
                            style="background:var(--primary);color:#fff;border-radius:10px;font-size:.875rem;font-weight:600">
                        <i class="bi bi-plus-lg me-1"></i> Create Group
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Edit Group Modal ── --}}
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;border:none">
            <div class="modal-header px-4 pt-4 pb-3" style="border-bottom:1px solid #f1f5f9">
                <h5 class="modal-title" style="font-weight:700">Edit Scope Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editForm">
                @csrf @method('PATCH')
                <div class="modal-body px-4 py-3">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.82rem">Group Name <span style="color:#dc2626">*</span></label>
                        <input type="text" name="name" id="edit-name" class="form-control"
                               style="border-radius:10px;border-color:#e2e8f0;font-size:.875rem" required>
                    </div>
                    <div>
                        <label class="form-label fw-semibold" style="font-size:.82rem">Description</label>
                        <textarea name="description" id="edit-desc" rows="3" class="form-control"
                                  style="border-radius:10px;border-color:#e2e8f0;font-size:.875rem;resize:none"></textarea>
                    </div>
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

@endsection

@push('scripts')
<script nonce="{{ csp_nonce() }}">
document.addEventListener('DOMContentLoaded', function () {

    // ── Scope Group Modals ────────────────────────────────────────
    var createModal = new bootstrap.Modal(document.getElementById('createModal'));
    var editModal   = new bootstrap.Modal(document.getElementById('editModal'));

    document.querySelectorAll('.js-open-create').forEach(function (btn) {
        btn.addEventListener('click', function () { createModal.show(); });
    });

    document.querySelectorAll('.js-open-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var group = JSON.parse(this.dataset.group);
            document.getElementById('edit-name').value = group.name;
            document.getElementById('edit-desc').value = group.description || '';
            document.getElementById('editForm').action = '/assessment-scope/' + group.id;
            editModal.show();
        });
    });

    // ── Criticality Labels Modal ──────────────────────────────────
    var critModalEl = document.getElementById('criticalityModal');
    if (!critModalEl) return;

    var criticalityModal = new bootstrap.Modal(critModalEl);
    var critRows         = document.getElementById('crit-rows');
    var critForm         = document.getElementById('criticalityForm');
    var critErrEl        = document.getElementById('crit-error');

    document.querySelectorAll('.js-open-criticality').forEach(function (btn) {
        btn.addEventListener('click', function () { criticalityModal.show(); });
    });

    // Auto-open if server returned a validation error for the criticality form
    @if($errors->has('criticality'))
    criticalityModal.show();
    @endif

    // Live-update badge text as user types
    critRows.addEventListener('input', function (e) {
        if (!e.target.classList.contains('crit-label-input')) return;
        var badge = e.target.closest('td').querySelector('.crit-badge');
        if (badge) { badge.textContent = e.target.value || '—'; }
    });

    // Delete row (delegate)
    critRows.addEventListener('click', function (e) {
        var btn = e.target.closest('.js-remove-crit-row');
        if (!btn) return;
        if (critRows.querySelectorAll('.crit-row').length <= 1) return;
        btn.closest('.crit-row').remove();
    });

    // Add new row
    document.getElementById('add-crit-row').addEventListener('click', function () {
        var row = document.createElement('tr');
        row.className = 'crit-row';

        var td1 = document.createElement('td');
        td1.style.cssText = 'padding:.3rem .4rem';
        var lvIn = document.createElement('input');
        lvIn.type = 'number'; lvIn.min = '1'; lvIn.max = '99'; lvIn.required = true;
        lvIn.className = 'form-control form-control-sm crit-level-input';
        lvIn.placeholder = '6';
        lvIn.style.cssText = 'border-radius:8px;border-color:#e2e8f0;font-size:.875rem;width:85px';
        td1.appendChild(lvIn);

        var td2 = document.createElement('td');
        td2.style.cssText = 'padding:.3rem .4rem';
        var lbIn = document.createElement('input');
        lbIn.type = 'text'; lbIn.maxLength = 60; lbIn.required = true;
        lbIn.className = 'form-control form-control-sm crit-label-input';
        lbIn.placeholder = 'e.g. Critical';
        lbIn.style.cssText = 'border-radius:8px;border-color:#e2e8f0;font-size:.875rem';
        td2.appendChild(lbIn);

        var td3 = document.createElement('td');
        td3.style.cssText = 'padding:.3rem .4rem;text-align:center';
        var delBtn = document.createElement('button');
        delBtn.type = 'button'; delBtn.className = 'btn btn-sm js-remove-crit-row';
        delBtn.style.cssText = 'border:1px solid #fecaca;color:#dc2626;background:#fff8f8;border-radius:8px;padding:.2rem .45rem';
        delBtn.innerHTML = '<i class="bi bi-trash3" style="font-size:.8rem"></i>';
        td3.appendChild(delBtn);

        row.appendChild(td1); row.appendChild(td2); row.appendChild(td3);
        critRows.appendChild(row);
        lvIn.focus();
    });

    // Submit: client-side validate, then assign indexed names
    critForm.addEventListener('submit', function (e) {
        var rows   = critRows.querySelectorAll('.crit-row');
        var levels = [], labels = [], errorMsg = '';

        rows.forEach(function (row) {
            var lv = parseInt(row.querySelector('.crit-level-input').value, 10);
            var lb = row.querySelector('.crit-label-input').value.trim().toLowerCase();
            if (!lv || lv < 1)      { errorMsg = 'All level numbers must be a valid integer (min 1).'; }
            if (!lb)                { errorMsg = 'All criticality names are required.'; }
            if (levels.includes(lv)){ errorMsg = 'Level numbers must be unique — remove the duplicate.'; }
            if (labels.includes(lb)){ errorMsg = 'Criticality names must be unique — remove the duplicate.'; }
            levels.push(lv);
            labels.push(lb);
        });

        if (errorMsg) {
            e.preventDefault();
            critErrEl.textContent = errorMsg;
            critErrEl.classList.remove('d-none');
            return;
        }

        critErrEl.classList.add('d-none');

        // Assign indexed names so PHP receives items[0][level], items[0][label], etc.
        rows.forEach(function (row, i) {
            row.querySelector('.crit-level-input').name = 'items[' + i + '][level]';
            row.querySelector('.crit-label-input').name = 'items[' + i + '][label]';
        });
    });

});
</script>
@endpush
