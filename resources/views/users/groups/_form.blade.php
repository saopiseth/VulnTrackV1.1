<style>
    :root { --lime: rgb(152,194,10); --lime-dark: rgb(118,151,7); --lime-muted: rgb(232,244,195); }
    .va-card { background:#fff; border:1px solid #e8f5c2; border-radius:14px; padding:1.5rem; margin-bottom:1.25rem; }
    .section-label {
        font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.7px;
        color:var(--lime-dark); margin-bottom:.85rem; padding-bottom:.4rem;
        border-bottom:2px solid var(--lime); display:flex; align-items:center; gap:.4rem;
    }
    .member-check { display:flex; align-items:center; gap:.6rem; padding:.55rem .75rem;
        border:1px solid #e8f5c2; border-radius:9px; cursor:pointer; transition:border-color .12s, background .12s; }
    .member-check:hover { border-color:var(--lime); background:var(--lime-muted); }
    .member-check input[type=checkbox]:checked ~ * { color:var(--lime-dark); }
    .member-check:has(input:checked) { border-color:var(--lime); background:var(--lime-muted); }
</style>

@php $isEdit = $group !== null; @endphp

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size:.73rem">
                <li class="breadcrumb-item"><a href="{{ route('users.index') }}" style="color:#94a3b8;text-decoration:none">Users</a></li>
                <li class="breadcrumb-item"><a href="{{ route('user-groups.index') }}" style="color:#94a3b8;text-decoration:none">Groups</a></li>
                <li class="breadcrumb-item active" style="color:#64748b">{{ $isEdit ? 'Edit' : 'New Group' }}</li>
            </ol>
        </nav>
        <h5 style="margin:0;font-weight:700;color:#0f172a">
            <i class="bi bi-people-fill me-2" style="color:var(--lime)"></i>
            {{ $isEdit ? 'Edit Group' : 'Create User Group' }}
        </h5>
    </div>
    <a href="{{ route('user-groups.index') }}" class="btn btn-sm"
        style="border:1.5px solid var(--lime);border-radius:9px;color:var(--lime-dark);background:#fff;font-weight:600;font-size:.81rem">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
</div>

@if($errors->any())
<div class="alert alert-danger mb-3" style="border-radius:10px;font-size:.85rem">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $errors->first() }}
</div>
@endif

<form method="POST"
    action="{{ $isEdit ? route('user-groups.update', $group) : route('user-groups.store') }}">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row g-3">

        {{-- Left column: details --}}
        <div class="col-lg-5">
            <div class="va-card">
                <div class="section-label"><i class="bi bi-info-circle-fill"></i>Group Details</div>

                <div class="mb-3">
                    <label class="form-label" style="font-size:.82rem;font-weight:600;color:#374151">
                        Group Name <span style="color:#dc2626">*</span>
                    </label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $group?->name) }}"
                        placeholder="e.g. Security Team"
                        style="border-radius:9px;font-size:.88rem">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-1">
                    <label class="form-label" style="font-size:.82rem;font-weight:600;color:#374151">Description</label>
                    <textarea name="description" class="form-control" rows="3"
                        placeholder="Optional description of this group's purpose…"
                        style="border-radius:9px;font-size:.88rem;resize:none">{{ old('description', $group?->description) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Right column: members --}}
        <div class="col-lg-7">
            <div class="va-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="section-label mb-0"><i class="bi bi-person-check-fill"></i>Members</div>
                    <span style="font-size:.75rem;color:#94a3b8">
                        <span id="sel-count">{{ count($memberIds) }}</span> selected
                    </span>
                </div>

                {{-- Quick search --}}
                <div class="input-group input-group-sm mb-3">
                    <span class="input-group-text" style="border-radius:8px 0 0 8px;background:#f8fafc"><i class="bi bi-search"></i></span>
                    <input type="text" id="member-search" class="form-control" placeholder="Search users…"
                        style="border-radius:0 8px 8px 0">
                </div>

                {{-- Select all / none --}}
                <div class="d-flex gap-2 mb-2">
                    <button type="button" id="btn-all" class="btn btn-sm"
                        style="font-size:.75rem;border-radius:7px;border:1px solid #e2e8f0;color:#374151;background:#fff;padding:.25rem .65rem">
                        Select all
                    </button>
                    <button type="button" id="btn-none" class="btn btn-sm"
                        style="font-size:.75rem;border-radius:7px;border:1px solid #e2e8f0;color:#374151;background:#fff;padding:.25rem .65rem">
                        Clear
                    </button>
                </div>

                <div id="member-list" style="max-height:320px;overflow-y:auto;display:flex;flex-direction:column;gap:.4rem">
                    @foreach($users as $user)
                    <label class="member-check" data-name="{{ strtolower($user->name) }} {{ strtolower($user->email) }}">
                        <input type="checkbox" name="members[]" value="{{ $user->id }}"
                            {{ in_array($user->id, old('members', $memberIds)) ? 'checked' : '' }}
                            style="accent-color:var(--lime);width:15px;height:15px;flex-shrink:0">
                        <div style="min-width:0;flex:1">
                            <div style="font-weight:600;color:#0f172a;font-size:.84rem;line-height:1.2">{{ $user->name }}</div>
                            <div style="font-size:.72rem;color:#94a3b8">{{ $user->email }}</div>
                        </div>
                        <span style="font-size:.65rem;font-weight:700;border-radius:20px;padding:.1rem .45rem;flex-shrink:0;
                            {{ $user->role === 'administrator' ? 'background:#fee2e2;color:#991b1b' : 'background:#e8f5c2;color:var(--lime-dark)' }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </label>
                    @endforeach

                    @if($users->isEmpty())
                    <div style="text-align:center;padding:2rem;color:#94a3b8;font-size:.82rem">No users found.</div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- Footer actions --}}
    <div class="d-flex gap-2 justify-content-end mt-1">
        <a href="{{ route('user-groups.index') }}" class="btn btn-sm"
            style="border:1.5px solid #cbd5e1;border-radius:9px;color:#64748b;background:#fff;font-weight:500;padding:.45rem 1.2rem">
            Cancel
        </a>
        <button type="submit" class="btn btn-sm"
            style="background:var(--lime);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.45rem 1.4rem">
            <i class="bi bi-check-lg me-1"></i>{{ $isEdit ? 'Save Changes' : 'Create Group' }}
        </button>
    </div>
</form>

@push('scripts')
<script>
// Live search
document.getElementById('member-search').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#member-list .member-check').forEach(el => {
        el.style.display = el.dataset.name.includes(q) ? '' : 'none';
    });
});

// Select all / none
document.getElementById('btn-all').addEventListener('click', () => {
    document.querySelectorAll('#member-list .member-check').forEach(el => {
        if (el.style.display !== 'none') el.querySelector('input').checked = true;
    });
    updateCount();
});
document.getElementById('btn-none').addEventListener('click', () => {
    document.querySelectorAll('#member-list input[type=checkbox]').forEach(cb => cb.checked = false);
    updateCount();
});

// Count
function updateCount() {
    const n = document.querySelectorAll('#member-list input:checked').length;
    document.getElementById('sel-count').textContent = n;
}
document.getElementById('member-list').addEventListener('change', updateCount);
</script>
@endpush
