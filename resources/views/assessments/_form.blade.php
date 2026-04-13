<style>
    .form-card { background:#fff; border:1px solid #e8f5c2; border-radius:14px; padding:1.5rem; margin-bottom:1.25rem; }
    .form-card h6 { font-size:.8rem; font-weight:700; color:rgb(118,151,7); text-transform:uppercase; letter-spacing:.8px; margin-bottom:1.25rem; padding-bottom:.6rem; border-bottom:2px solid rgb(152,194,10); }
    .form-label { font-size:.82rem; font-weight:600; color:#374151; margin-bottom:.35rem; }
    .form-control, .form-select {
        font-size:.875rem; border-color:#e2e8f0; border-radius:9px; padding:.55rem .85rem; color:#0f172a;
        transition: border-color .2s, box-shadow .2s;
    }
    .form-control:focus, .form-select:focus {
        border-color:rgb(152,194,10); box-shadow:0 0 0 3px rgba(152,194,10,.15);
    }
    .form-control.is-invalid, .form-select.is-invalid { border-color:#dc2626; }
    .invalid-feedback { font-size:.78rem; }

    .btn-submit {
        background:rgb(152,194,10);
        color:#fff; border:none; border-radius:10px;
        font-weight:600; font-size:.9rem; padding:.65rem 2rem;
        box-shadow:0 4px 12px rgba(152,194,10,.35);
        transition:all .2s;
    }
    .btn-submit:hover { color:#fff; transform:translateY(-1px); box-shadow:0 6px 18px rgba(152,194,10,.45); }

    .criteria-toggle { display:inline-flex; border:1.5px solid #e2e8f0; border-radius:8px; overflow:hidden; }
    .criteria-opt {
        padding:.32rem .9rem; font-size:.8rem; font-weight:600; cursor:pointer;
        color:#94a3b8; background:#fff; transition:all .15s; user-select:none; margin:0;
    }
    .criteria-opt.active-no  { background:#f1f5f9; color:#374151; }
    .criteria-opt.active-yes { background:rgb(152,194,10); color:#fff; }

    .upload-zone {
        border:1.5px dashed #cbd5e1; border-radius:8px; padding:.45rem .6rem;
        display:flex; flex-direction:column; align-items:center; justify-content:center;
        cursor:pointer; transition:all .2s; min-height:54px;
    }
    .upload-zone:hover { border-color:rgb(152,194,10); background:#f9fde8; }
    .upload-zone.has-file { border-color:rgb(152,194,10); background:#f0fdf4; }
</style>

<form method="POST" action="{{ $action }}" enctype="multipart/form-data">
    @csrf
    @if($method === 'PUT') @method('PUT') @endif

    @if($errors->any())
        <div class="alert d-flex align-items-center gap-2 mb-3" style="background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;border-radius:10px;font-size:.875rem">
            <i class="bi bi-exclamation-triangle-fill"></i>
            Please fix the errors below before submitting.
        </div>
    @endif

    <div class="row g-3 align-items-start">

        {{-- Left column: Criteria --}}
        <div class="col-lg-9">

            {{-- Applicable Criteria --}}
            <div class="form-card" style="margin-bottom:1.25rem">
                <h6><i class="bi bi-list-check me-2"></i>Applicable Criteria</h6>
                <p style="font-size:.82rem;color:#64748b;margin-bottom:1rem">
                    Select <strong>Yes</strong> or <strong>No</strong> for each criterion.
                </p>
                <div class="table-responsive">
                    <table class="table" style="font-size:.875rem;margin:0">
                        <thead style="background:rgb(240,248,210)">
                            <tr>
                                <th style="width:38px;color:rgb(118,151,7);font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-color:#e2e8f0;padding:.65rem .75rem">#</th>
                                <th style="color:rgb(118,151,7);font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-color:#e2e8f0;padding:.65rem .75rem;min-width:160px">Criteria</th>
                                <th style="color:rgb(118,151,7);font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-color:#e2e8f0;padding:.65rem .75rem">Description</th>
                                <th style="width:120px;text-align:center;color:rgb(118,151,7);font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-color:#e2e8f0;padding:.65rem .75rem">Yes / No</th>
                                <th style="width:145px;color:rgb(118,151,7);font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-color:#e2e8f0;padding:.65rem .75rem">Status</th>
                                <th style="width:190px;color:rgb(118,151,7);font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-color:#e2e8f0;padding:.65rem .75rem">Evidence</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($criteria as $i => $c)
                            @php
                                $val        = old($c['field'], $assessment?->{$c['field']} ?? 0);
                                $currStatus = old($c['field'].'_status', $assessment?->{$c['field'].'_status'} ?? 'Not Started');
                                $currFile   = $assessment?->{$c['field'].'_evidence'};
                            @endphp
                            <tr style="border-color:#f1f5f9">
                                <td style="color:#94a3b8;font-weight:600;padding:.75rem .75rem;vertical-align:top;border-color:#f1f5f9">{{ $i + 1 }}</td>

                                <td style="font-weight:600;color:#0f172a;padding:.75rem .75rem;vertical-align:top;border-color:#f1f5f9">
                                    {{ $c['label'] }}
                                </td>

                                <td style="color:#64748b;padding:.75rem .75rem;vertical-align:top;border-color:#f1f5f9;line-height:1.6;font-size:.82rem">
                                    {{ $c['description'] }}
                                </td>

                                <td style="text-align:center;vertical-align:top;padding:.75rem .75rem;border-color:#f1f5f9">
                                    <div class="criteria-toggle">
                                        <input type="radio" name="{{ $c['field'] }}" id="no_{{ $c['field'] }}"
                                            value="0" class="d-none" {{ !$val ? 'checked' : '' }}>
                                        <label class="criteria-opt {{ !$val ? 'active-no' : '' }}" for="no_{{ $c['field'] }}">No</label>

                                        <input type="radio" name="{{ $c['field'] }}" id="yes_{{ $c['field'] }}"
                                            value="1" class="d-none" {{ $val ? 'checked' : '' }}>
                                        <label class="criteria-opt {{ $val ? 'active-yes' : '' }}" for="yes_{{ $c['field'] }}">Yes</label>
                                    </div>
                                </td>

                                <td style="vertical-align:top;padding:.75rem .75rem;border-color:#f1f5f9">
                                    <select name="{{ $c['field'] }}_status" class="form-select form-select-sm" style="font-size:.8rem;border-radius:8px;border-color:#e2e8f0">
                                        @foreach($statuses as $st)
                                            <option value="{{ $st }}" {{ $currStatus === $st ? 'selected' : '' }}>{{ $st }}</option>
                                        @endforeach
                                    </select>
                                </td>

                                <td style="vertical-align:top;padding:.75rem .75rem;border-color:#f1f5f9">
                                    @if($currFile)
                                        <div class="d-flex align-items-center gap-1 mb-1">
                                            <a href="{{ Storage::url($currFile) }}" target="_blank"
                                                style="font-size:.75rem;color:rgb(118,151,7);font-weight:600;text-decoration:none;max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block"
                                                title="{{ basename($currFile) }}">
                                                <i class="bi bi-paperclip me-1"></i>{{ basename($currFile) }}
                                            </a>
                                        </div>
                                    @endif
                                    <div class="upload-zone" id="zone_{{ $c['field'] }}" onclick="document.getElementById('file_{{ $c['field'] }}').click()">
                                        <i class="bi bi-cloud-upload" style="font-size:1.1rem;color:#94a3b8"></i>
                                        <span id="fname_{{ $c['field'] }}" style="font-size:.72rem;color:#94a3b8;margin-top:2px">
                                            {{ $currFile ? 'Replace file' : 'Click to upload' }}
                                        </span>
                                    </div>
                                    <input type="file" id="file_{{ $c['field'] }}" name="{{ $c['field'] }}_evidence"
                                        class="d-none"
                                        accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.zip"
                                        onchange="showFileName('{{ $c['field'] }}', this)">
                                    <div style="font-size:.68rem;color:#94a3b8;margin-top:3px">PDF, DOC, XLS, IMG, ZIP · max 10MB</div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Comments --}}
            <div class="form-card" style="margin-bottom:0">
                <h6><i class="bi bi-chat-left-text me-2"></i>Comments</h6>
                <textarea name="comments" class="form-control @error('comments') is-invalid @enderror"
                    rows="4" placeholder="Additional notes or observations...">{{ old('comments', $assessment?->comments) }}</textarea>
                @error('comments')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

        </div>

        {{-- Right column: Metadata --}}
        <div class="col-lg-3" style="position:sticky;top:1rem;align-self:flex-start">

            {{-- Project Info --}}
            <div class="form-card">
                <h6><i class="bi bi-folder2-open me-2"></i>Project Information</h6>
                <div class="d-flex flex-column gap-3">
                    <div>
                        <label class="form-label">Project Name <span style="color:#dc2626">*</span></label>
                        <input type="text" name="assessment_type"
                            class="form-control @error('assessment_type') is-invalid @enderror"
                            placeholder="Enter project name"
                            value="{{ old('assessment_type', $assessment?->assessment_type) }}"
                            required>
                        @error('assessment_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label">Priority <span style="color:#dc2626">*</span></label>
                        <select name="priority" class="form-select @error('priority') is-invalid @enderror">
                            @foreach(['Critical','High','Medium','Low'] as $p)
                                <option value="{{ $p }}" {{ old('priority', $assessment?->priority ?? 'Medium') === $p ? 'selected' : '' }}>{{ $p }}</option>
                            @endforeach
                        </select>
                        @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label">Status <span style="color:#dc2626">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            @foreach(['Open','In Progress','Closed'] as $s)
                                <option value="{{ $s }}" {{ old('status', $assessment?->status ?? 'Open') === $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            {{-- Dates --}}
            <div class="form-card">
                <h6><i class="bi bi-calendar3 me-2"></i>Dates</h6>
                <div class="d-flex flex-column gap-3">
                    <div>
                        <label class="form-label">Project Kickoff</label>
                        <input type="date" name="project_kickoff" class="form-control @error('project_kickoff') is-invalid @enderror"
                            value="{{ old('project_kickoff', $assessment?->project_kickoff?->format('Y-m-d')) }}">
                        @error('project_kickoff')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror"
                            value="{{ old('due_date', $assessment?->due_date?->format('Y-m-d')) }}">
                        @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label">Complete Date</label>
                        <input type="date" name="complete_date" class="form-control @error('complete_date') is-invalid @enderror"
                            value="{{ old('complete_date', $assessment?->complete_date?->format('Y-m-d')) }}">
                        @error('complete_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            {{-- Personnel --}}
            <div class="form-card">
                <h6><i class="bi bi-people me-2"></i>Personnel</h6>
                <div>
                    <label class="form-label">Project Coordinator</label>
                    <div class="input-group">
                        <span class="input-group-text" style="background:#f8fafc;border-color:#e2e8f0"><i class="bi bi-person" style="color:#94a3b8"></i></span>
                        <input type="text" name="project_coordinator" class="form-control @error('project_coordinator') is-invalid @enderror"
                            placeholder="Full name" value="{{ old('project_coordinator', $assessment?->project_coordinator) }}">
                    </div>
                    @error('project_coordinator')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- BCD Reference --}}
            <div class="form-card">
                <h6><i class="bi bi-link-45deg me-2"></i>BCD Reference</h6>
                <div class="d-flex flex-column gap-3">
                    <div>
                        <label class="form-label">BCD ID</label>
                        <input type="text" name="bcd_id" class="form-control @error('bcd_id') is-invalid @enderror"
                            placeholder="e.g. BCD-2024-001" value="{{ old('bcd_id', $assessment?->bcd_id) }}"
                            style="font-family:monospace">
                        @error('bcd_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label">BCD URL</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background:#f8fafc;border-color:#e2e8f0"><i class="bi bi-globe" style="color:#94a3b8"></i></span>
                            <input type="url" name="bcd_url" class="form-control @error('bcd_url') is-invalid @enderror"
                                placeholder="https://" value="{{ old('bcd_url', $assessment?->bcd_url) }}">
                        </div>
                        @error('bcd_url')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <div class="d-flex flex-column gap-2">
                <button type="submit" class="btn btn-submit w-100">
                    <i class="bi bi-{{ $assessment ? 'check-lg' : 'plus-lg' }} me-2"></i>
                    {{ $assessment ? 'Update Assessment' : 'Create Assessment' }}
                </button>
                <a href="{{ route('assessments.index') }}" class="btn w-100" style="border:1.5px solid rgb(152,194,10);border-radius:10px;font-weight:500;color:rgb(118,151,7);padding:.65rem 1.5rem;text-align:center">
                    Cancel
                </a>
            </div>

        </div>
    </div>

</form>

<script>
document.querySelectorAll('.criteria-toggle input[type=radio]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        const toggle = this.closest('.criteria-toggle');
        toggle.querySelectorAll('label.criteria-opt').forEach(function(lbl) {
            lbl.classList.remove('active-no', 'active-yes');
        });
        const activeLbl = toggle.querySelector('label[for="' + this.id + '"]');
        if (this.value === '1') activeLbl.classList.add('active-yes');
        else activeLbl.classList.add('active-no');
    });
});

function showFileName(field, input) {
    const zone  = document.getElementById('zone_' + field);
    const label = document.getElementById('fname_' + field);
    if (input.files && input.files[0]) {
        const name = input.files[0].name;
        const size = (input.files[0].size / 1024).toFixed(0) + ' KB';
        label.textContent = name + ' (' + size + ')';
        label.style.color = 'rgb(118,151,7)';
        zone.classList.add('has-file');
    }
}
</script>
