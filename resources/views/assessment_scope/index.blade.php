@extends('layouts.app')
@section('title', 'Assessment Scope')

@section('content')

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
        <h4 class="mb-0">Assessment Scope</h4>
        <p class="mb-0">Named scope groups — each group holds the in-scope assets for an assessment</p>
    </div>
    <button class="btn btn-primary" onclick="openCreate()"
            style="background:var(--primary);border-color:var(--primary);border-radius:10px;font-size:.875rem;font-weight:600">
        <i class="bi bi-plus-lg me-1"></i> New Scope Group
    </button>
</div>

@if(session('success'))
<div class="alert d-flex align-items-center gap-2 mb-4" style="border-radius:12px;border:none;background:#f0fdf4;color:#166534;">
    <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
</div>
@endif

@if($groups->isEmpty())
<div class="card text-center py-5">
    <div style="color:#94a3b8">
        <i class="bi bi-diagram-3" style="font-size:2.5rem;display:block;margin-bottom:.75rem"></i>
        <div style="font-weight:600;color:#374151;margin-bottom:.35rem">No scope groups yet</div>
        <div style="font-size:.85rem;margin-bottom:1rem">Create a named scope group to start defining in-scope assets.</div>
        <button class="btn btn-primary" onclick="openCreate()"
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
                                <button class="dropdown-item" onclick='openEdit({{ json_encode(["id"=>$group->id,"name"=>$group->name,"description"=>$group->description]) }})'>
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
<script>
function openCreate() { new bootstrap.Modal(document.getElementById('createModal')).show(); }

function openEdit(group) {
    document.getElementById('edit-name').value = group.name;
    document.getElementById('edit-desc').value = group.description || '';
    document.getElementById('editForm').action = '/assessment-scope/' + group.id;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
@endpush
