{{-- Shared form fields for Add / Edit modals.
     $prefix = 'add' or 'edit'  (used for HTML id attributes)
--}}
@php $isEdit = ($prefix === 'edit'); @endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.82rem">IP Address</label>
        <input type="text" name="ip_address" id="{{ $prefix }}-ip"
               class="form-control" placeholder="e.g. 192.168.1.10"
               style="border-radius:10px;border-color:#e2e8f0;font-size:.875rem">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.82rem">Hostname</label>
        <input type="text" name="hostname" id="{{ $prefix }}-hostname"
               class="form-control" placeholder="e.g. web-server-01"
               style="border-radius:10px;border-color:#e2e8f0;font-size:.875rem">
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.82rem">System Name</label>
        <input type="text" name="system_name" id="{{ $prefix }}-system"
               class="form-control" placeholder="e.g. Core Banking System"
               style="border-radius:10px;border-color:#e2e8f0;font-size:.875rem">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.82rem">System Owner</label>
        <input type="text" name="system_owner" id="{{ $prefix }}-owner"
               class="form-control" placeholder="e.g. IT Department"
               style="border-radius:10px;border-color:#e2e8f0;font-size:.875rem">
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.82rem">System Criticality</label>
        <select name="system_criticality" id="{{ $prefix }}-criticality"
                class="form-select" style="border-radius:10px;border-color:#e2e8f0;font-size:.875rem">
            <option value="">— Select —</option>
            @foreach(\App\Models\AssessmentScope::criticalityLevels() as $k => $lv)
            <option value="{{ $k }}">{{ $k }} – {{ $lv['label'] }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.82rem">Identified Scope</label>
        <select name="identified_scope" id="{{ $prefix }}-scope"
                class="form-select" style="border-radius:10px;border-color:#e2e8f0;font-size:.875rem">
            <option value="">— Select —</option>
            @foreach(\App\Models\AssessmentScope::scopeOptions() as $s)
            <option value="{{ $s }}">{{ $s }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.82rem">Environment</label>
        <select name="environment" id="{{ $prefix }}-env"
                class="form-select" style="border-radius:10px;border-color:#e2e8f0;font-size:.875rem">
            <option value="">— Select —</option>
            @foreach(\App\Models\AssessmentScope::environmentOptions() as $e)
            <option value="{{ $e }}">{{ $e }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold" style="font-size:.82rem">Location</label>
        <select name="location" id="{{ $prefix }}-loc"
                class="form-select" style="border-radius:10px;border-color:#e2e8f0;font-size:.875rem">
            <option value="">— Select —</option>
            @foreach(\App\Models\AssessmentScope::locationOptions() as $l)
            <option value="{{ $l }}">{{ $l }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-12">
        <label class="form-label fw-semibold" style="font-size:.82rem">Notes <span style="color:#94a3b8;font-weight:400">(optional)</span></label>
        <textarea name="notes" id="{{ $prefix }}-notes" rows="2"
                  class="form-control" placeholder="Any additional information…"
                  style="border-radius:10px;border-color:#e2e8f0;font-size:.875rem;resize:none"></textarea>
    </div>
</div>
