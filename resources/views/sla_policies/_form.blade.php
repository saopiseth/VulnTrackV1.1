<style>
    :root { --lime: rgb(152,194,10); --lime-dark: rgb(118,151,7); --lime-muted: rgb(232,244,195); }
    .va-card { background:#fff; border:1px solid #e8f5c2; border-radius:14px; padding:1.5rem; margin-bottom:1.25rem; }
    .section-label {
        font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.7px;
        color:var(--lime-dark); margin-bottom:.85rem; padding-bottom:.4rem;
        border-bottom:2px solid var(--lime); display:flex; align-items:center; gap:.4rem;
    }
    .sla-day-input { max-width:110px; }
    .sev-sla-row { display:flex; align-items:center; gap:1rem; padding:.75rem 1rem;
        border:1px solid #e8f5c2; border-radius:10px; background:#fff; }
    .sev-sla-row:hover { background:var(--lime-muted); }
</style>

@php $isEdit = $policy !== null; @endphp

{{-- Header --}}
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size:.73rem">
                <li class="breadcrumb-item"><a href="{{ route('sla-policies.index') }}" style="color:#94a3b8;text-decoration:none">SLA Policies</a></li>
                <li class="breadcrumb-item active" style="color:#64748b">{{ $isEdit ? 'Edit' : 'New Policy' }}</li>
            </ol>
        </nav>
        <h5 style="margin:0;font-weight:700;color:#0f172a">
            <i class="bi bi-stopwatch-fill me-2" style="color:var(--lime)"></i>
            {{ $isEdit ? 'Edit SLA Policy' : 'Create SLA Policy' }}
        </h5>
    </div>
    <a href="{{ route('sla-policies.index') }}" class="btn btn-sm"
        style="border:1.5px solid var(--lime);border-radius:9px;color:var(--lime-dark);background:#fff;font-weight:600;font-size:.81rem">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
</div>

@if($errors->any())
<div class="alert alert-danger mb-3" style="border-radius:10px;font-size:.85rem">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $errors->first() }}
</div>
@endif

<form method="POST" action="{{ $isEdit ? route('sla-policies.update', $policy) : route('sla-policies.store') }}">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row g-3">

        {{-- Left: details --}}
        <div class="col-lg-5">
            <div class="va-card">
                <div class="section-label"><i class="bi bi-info-circle-fill"></i>Policy Details</div>

                <div class="mb-3">
                    <label class="form-label" style="font-size:.82rem;font-weight:600;color:#374151">
                        Policy Name <span style="color:#dc2626">*</span>
                    </label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $policy?->name) }}"
                        placeholder="e.g. Standard Enterprise SLA"
                        style="border-radius:9px;font-size:.88rem">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" style="font-size:.82rem;font-weight:600;color:#374151">Description</label>
                    <textarea name="description" class="form-control" rows="3"
                        placeholder="Optional description of this SLA policy…"
                        style="border-radius:9px;font-size:.88rem;resize:none">{{ old('description', $policy?->description) }}</textarea>
                </div>

                <div class="form-check" style="padding-left:0">
                    <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;font-size:.84rem;font-weight:600;color:#374151">
                        <input type="hidden" name="is_default" value="0">
                        <input type="checkbox" name="is_default" value="1" class="form-check-input mt-0"
                            style="accent-color:var(--lime-dark);width:16px;height:16px"
                            {{ old('is_default', $policy?->is_default) ? 'checked' : '' }}>
                        Set as default policy
                    </label>
                    <div style="font-size:.74rem;color:#94a3b8;margin-top:.25rem;padding-left:1.6rem">
                        Default policy is auto-applied to new assessments.
                    </div>
                </div>
            </div>

            {{-- Info card --}}
            <div style="background:#f8fafc;border:1px solid #e8f5c2;border-radius:12px;padding:1rem 1.25rem">
                <div style="font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;margin-bottom:.5rem">
                    <i class="bi bi-lightbulb-fill me-1" style="color:var(--lime)"></i>How SLA works
                </div>
                <div style="font-size:.78rem;color:#475569;line-height:1.7">
                    The remediation deadline for each finding is calculated as:<br>
                    <strong>First Seen + SLA Days</strong><br><br>
                    Statuses shown on the findings page:<br>
                    <span style="background:#d1fae5;color:#065f46;border-radius:4px;padding:.05rem .4rem;font-weight:700;font-size:.72rem">On Track</span> — within deadline<br>
                    <span style="background:#fef9c3;color:#854d0e;border-radius:4px;padding:.05rem .4rem;font-weight:700;font-size:.72rem">Approaching</span> — within 7 days of deadline<br>
                    <span style="background:#fee2e2;color:#991b1b;border-radius:4px;padding:.05rem .4rem;font-weight:700;font-size:.72rem">Breached</span> — past deadline
                </div>
            </div>
        </div>

        {{-- Right: SLA days --}}
        <div class="col-lg-7">
            <div class="va-card">
                <div class="section-label"><i class="bi bi-clock-history"></i>Remediation Timeframes (Days)</div>
                <p style="font-size:.78rem;color:#64748b;margin-bottom:1rem">
                    Maximum number of calendar days to remediate a finding of each severity.
                </p>

                @php
                    $severities = [
                        'critical' => ['label'=>'Critical', 'bg'=>'#fee2e2', 'color'=>'#991b1b', 'icon'=>'bi-exclamation-octagon-fill', 'default'=>7],
                        'high'     => ['label'=>'High',     'bg'=>'#ffedd5', 'color'=>'#9a3412', 'icon'=>'bi-exclamation-triangle-fill','default'=>30],
                        'medium'   => ['label'=>'Medium',   'bg'=>'#fef9c3', 'color'=>'#854d0e', 'icon'=>'bi-dash-circle-fill',         'default'=>90],
                        'low'      => ['label'=>'Low',      'bg'=>'#f1f5f9', 'color'=>'#475569', 'icon'=>'bi-info-circle-fill',         'default'=>180],
                    ];
                @endphp

                <div style="display:flex;flex-direction:column;gap:.6rem">
                    @foreach($severities as $key => $sev)
                    <div class="sev-sla-row">
                        <span style="display:inline-flex;align-items:center;gap:.35rem;font-size:.78rem;font-weight:700;
                            background:{{ $sev['bg'] }};color:{{ $sev['color'] }};
                            padding:.2rem .65rem;border-radius:20px;min-width:85px;justify-content:center;flex-shrink:0">
                            <i class="bi {{ $sev['icon'] }}" style="font-size:.72rem"></i>
                            {{ $sev['label'] }}
                        </span>
                        <div style="flex:1;min-width:0">
                            <div style="font-size:.74rem;color:#94a3b8">Max remediation window</div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                            <input type="number" name="{{ $key }}_days"
                                class="form-control form-control-sm sla-day-input @error($key.'_days') is-invalid @enderror"
                                value="{{ old($key.'_days', $policy?->{$key.'_days'} ?? $sev['default']) }}"
                                min="1" max="3650" required
                                style="border-radius:8px;text-align:center;font-weight:700;font-size:.88rem">
                            <span style="font-size:.78rem;color:#94a3b8;white-space:nowrap">days</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

    {{-- Footer --}}
    <div class="d-flex gap-2 justify-content-end mt-1">
        <a href="{{ route('sla-policies.index') }}" class="btn btn-sm"
            style="border:1.5px solid #cbd5e1;border-radius:9px;color:#64748b;background:#fff;font-weight:500;padding:.45rem 1.2rem">
            Cancel
        </a>
        <button type="submit" class="btn btn-sm"
            style="background:var(--lime);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.45rem 1.4rem">
            <i class="bi bi-check-lg me-1"></i>{{ $isEdit ? 'Save Changes' : 'Create Policy' }}
        </button>
    </div>
</form>
