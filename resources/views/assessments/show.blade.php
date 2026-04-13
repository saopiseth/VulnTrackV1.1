@extends('layouts.app')
@section('title', 'Assessment — ' . $assessment->assessment_type)

@section('content')

<style>
    :root {
        --lime: rgb(152, 194, 10);
        --lime-dark: rgb(118, 151, 7);
        --lime-light: rgb(240, 248, 210);
        --lime-muted: rgb(232, 244, 195);
    }
    .detail-card { background:#fff; border:1px solid #e8f5c2; border-radius:14px; padding:1.5rem; margin-bottom:1.25rem; }
    .detail-card h6 { font-size:.8rem; font-weight:700; color:var(--lime-dark); text-transform:uppercase; letter-spacing:.8px; margin-bottom:1.25rem; padding-bottom:.6rem; border-bottom:2px solid var(--lime); }
    .detail-row { display:flex; flex-direction:column; gap:.25rem; }
    .detail-row .dl { font-size:.75rem; font-weight:600; color:#94a3b8; text-transform:uppercase; letter-spacing:.5px; }
    .detail-row .dv { font-size:.9rem; color:#1a2e05; font-weight:500; }
    .badge-pill { padding:.3rem .8rem; border-radius:20px; font-size:.78rem; font-weight:700; display:inline-block; }
    .scope-item { display:flex; align-items:center; gap:.6rem; padding:.5rem .75rem; border-radius:9px; font-size:.85rem; font-weight:500; }
    .scope-yes { background:var(--lime-light); color:var(--lime-dark); }
    .scope-no  { background:#f8fafc; color:#94a3b8; }
    .criteria-status-badge { display:inline-block; padding:.18rem .6rem; border-radius:20px; font-size:.7rem; font-weight:700; white-space:nowrap; }
    .cs-not-started { background:#f1f5f9; color:#64748b; }
    .cs-in-progress { background:#fef9c3; color:#854d0e; }
    .cs-completed   { background:var(--lime-light); color:var(--lime-dark); }
    .cs-na          { background:#f8fafc; color:#94a3b8; }
    thead.lime-head th { background:var(--lime-muted) !important; color:var(--lime-dark) !important; }
</style>

{{-- Header --}}
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h4>{{ $assessment->assessment_type }}</h4>
        <p>Assessment ID #{{ $assessment->id }} &middot; Created {{ $assessment->created_at->format('d M Y') }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('update', $assessment)
        <a href="{{ route('assessments.edit', $assessment) }}" class="btn btn-sm" style="background:rgb(152,194,10);color:#fff;border-radius:9px;font-weight:600;border:none;padding:.45rem 1rem">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        @endcan
        <a href="{{ route('assessments.report', $assessment) }}" class="btn btn-sm" style="background:#0f172a;color:#fff;border-radius:9px;font-weight:600;border:none;padding:.45rem 1rem">
            <i class="bi bi-file-earmark-pdf me-1"></i> Download Report
        </a>
        <a href="{{ route('assessments.index') }}" class="btn btn-sm" style="border:1.5px solid rgb(152,194,10);border-radius:9px;font-size:.85rem;font-weight:500;color:rgb(118,151,7);background:#fff">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row g-3 align-items-start">

    {{-- Left column --}}
    <div class="col-lg-9">

        {{-- Status + Priority strip --}}
        <div class="detail-card" style="padding:.9rem 1.5rem;margin-bottom:1.25rem">
            <div class="d-flex flex-wrap gap-4 align-items-center">
                <div>
                    <span style="font-size:.72rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Status</span><br>
                    @php $sc = str_replace(' ','-',strtolower($assessment->status)); @endphp
                    <span class="badge-pill
                        {{ $assessment->status === 'Open' ? 'bg-danger text-white' : ($assessment->status === 'In Progress' ? 'bg-warning text-dark' : 'bg-success text-white') }}">
                        {{ $assessment->status }}
                    </span>
                </div>
                <div style="width:1px;height:32px;background:#e2e8f0"></div>
                <div>
                    <span style="font-size:.72rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px">Priority</span><br>
                    @php
                        $pc = ['Critical'=>'danger','High'=>'warning','Medium'=>'info','Low'=>'secondary'];
                        $pcc = $pc[$assessment->priority] ?? 'secondary';
                    @endphp
                    <span class="badge-pill bg-{{ $pcc }} {{ in_array($assessment->priority,['High','Medium']) ? 'text-dark' : 'text-white' }}">
                        {{ $assessment->priority }}
                    </span>
                </div>
                <div style="width:1px;height:32px;background:#e2e8f0"></div>
                <div>
                    <span style="font-size:.72rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px">BCD ID</span><br>
                    <span style="font-family:monospace;font-size:.9rem;color:#0f172a;font-weight:600">{{ $assessment->bcd_id ?? '—' }}</span>
                </div>
                @if($assessment->bcd_url)
                <div style="width:1px;height:32px;background:#e2e8f0"></div>
                <div>
                    <span style="font-size:.72rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px">BCD URL</span><br>
                    <a href="{{ $assessment->bcd_url }}" target="_blank" style="color:rgb(118,151,7);font-size:.85rem;font-weight:600;word-break:break-all">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Open Link
                    </a>
                </div>
                @endif
            </div>
        </div>

        {{-- Criteria Detail (evidence + status) --}}
        <div class="detail-card" style="margin-bottom:0">
            <h6><i class="bi bi-shield-check me-2"></i>Criteria — Evidence &amp; Status</h6>
            <div class="table-responsive">
                <table class="table" style="font-size:.82rem;margin:0">
                    <thead class="lime-head">
                        <tr>
                            <th style="width:30px;color:#64748b;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-color:#e2e8f0;padding:.55rem .65rem">#</th>
                            <th style="color:#64748b;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-color:#e2e8f0;padding:.55rem .65rem;min-width:140px">Criteria</th>
                            <th style="width:80px;text-align:center;color:#64748b;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-color:#e2e8f0;padding:.55rem .65rem">Applicable</th>
                            <th style="width:130px;color:#64748b;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-color:#e2e8f0;padding:.55rem .65rem">Status</th>
                            <th style="color:#64748b;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;border-color:#e2e8f0;padding:.55rem .65rem">Evidence</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($criteria as $i => $c)
                        @php
                            $active     = $assessment->{$c['field']};
                            $critStatus = $assessment->{$c['field'].'_status'} ?? 'Not Started';
                            $evidence   = $assessment->{$c['field'].'_evidence'};
                            $csClass    = match($critStatus) {
                                'In Progress' => 'cs-in-progress',
                                'Completed'   => 'cs-completed',
                                'N/A'         => 'cs-na',
                                default       => 'cs-not-started',
                            };
                        @endphp
                        <tr style="border-color:#f1f5f9">
                            <td style="color:#94a3b8;font-weight:600;padding:.6rem .65rem;vertical-align:middle;border-color:#f1f5f9">{{ $i + 1 }}</td>
                            <td style="font-weight:600;color:{{ $active ? '#0f172a' : '#94a3b8' }};padding:.6rem .65rem;vertical-align:middle;border-color:#f1f5f9">
                                {{ $c['label'] }}
                            </td>
                            <td style="text-align:center;vertical-align:middle;padding:.6rem .65rem;border-color:#f1f5f9">
                                <span class="scope-item {{ $active ? 'scope-yes' : 'scope-no' }}" style="padding:.18rem .6rem;font-size:.7rem;white-space:nowrap;display:inline-flex">
                                    <i class="bi bi-{{ $active ? 'check-circle-fill' : 'x-circle' }}"></i>
                                    {{ $active ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td style="vertical-align:middle;padding:.6rem .65rem;border-color:#f1f5f9">
                                <span class="criteria-status-badge {{ $csClass }}">{{ $critStatus }}</span>
                            </td>
                            <td style="vertical-align:middle;padding:.6rem .65rem;border-color:#f1f5f9">
                                @if($evidence)
                                    <a href="{{ Storage::url($evidence) }}" target="_blank"
                                        style="font-size:.78rem;color:rgb(118,151,7);font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                                        title="{{ basename($evidence) }}">
                                        <i class="bi bi-paperclip" style="flex-shrink:0"></i>
                                        {{ basename($evidence) }}
                                    </a>
                                @else
                                    <span style="color:#cbd5e1;font-size:.78rem">No file</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Comments --}}
        <div class="detail-card" style="margin-top:1.25rem;margin-bottom:0">
            <h6><i class="bi bi-chat-left-text me-2"></i>Comments</h6>
            @if($assessment->comments)
                <p style="font-size:.9rem;color:#374151;line-height:1.7;margin:0;white-space:pre-line">{{ $assessment->comments }}</p>
            @else
                <p style="color:#94a3b8;font-size:.875rem;margin:0">No comments added.</p>
            @endif
        </div>

    </div>

    {{-- Right column --}}
    <div class="col-lg-3" style="position:sticky;top:1rem;align-self:flex-start">

        {{-- Dates --}}
        <div class="detail-card">
            <h6><i class="bi bi-calendar3 me-2"></i>Dates</h6>
            <div class="d-flex flex-column gap-3">
                <div class="detail-row">
                    <span class="dl">Assessment Kickoff</span>
                    <span class="dv">{{ $assessment->project_kickoff?->format('d M Y') ?? '—' }}</span>
                </div>
                <div class="detail-row">
                    <span class="dl">Due Date</span>
                    <span class="dv" style="{{ $assessment->due_date?->isPast() && $assessment->status !== 'Closed' ? 'color:#dc2626;font-weight:700' : '' }}">
                        {{ $assessment->due_date?->format('d M Y') ?? '—' }}
                        @if($assessment->due_date?->isPast() && $assessment->status !== 'Closed')
                            <span style="font-size:.72rem;background:#fee2e2;color:#dc2626;padding:.1rem .4rem;border-radius:5px;margin-left:.3rem">Overdue</span>
                        @endif
                    </span>
                </div>
                <div class="detail-row">
                    <span class="dl">Complete Date</span>
                    <span class="dv">{{ $assessment->complete_date?->format('d M Y') ?? '—' }}</span>
                </div>
            </div>
        </div>

        {{-- Personnel --}}
        <div class="detail-card">
            <h6><i class="bi bi-people me-2"></i>Personnel</h6>
            <div class="d-flex flex-column gap-3">
                <div class="detail-row">
                    <span class="dl">Assessment Coordinator</span>
                    <span class="dv">
                        @if($assessment->project_coordinator)
                            <i class="bi bi-person-circle" style="color:rgb(152,194,10);margin-right:.3rem"></i>{{ $assessment->project_coordinator }}
                        @else —
                        @endif
                    </span>
                </div>
            </div>
        </div>

        {{-- Meta --}}
        <div class="detail-card" style="margin-bottom:1.25rem">
            <h6><i class="bi bi-info-circle me-2"></i>Record Info</h6>
            <div class="d-flex flex-column gap-3">
                <div class="detail-row">
                    <span class="dl">Created By</span>
                    <span class="dv">{{ $assessment->creator?->name ?? 'System' }}</span>
                </div>
                <div class="detail-row">
                    <span class="dl">Created At</span>
                    <span class="dv">{{ $assessment->created_at->format('d M Y, h:i A') }}</span>
                </div>
                <div class="detail-row">
                    <span class="dl">Last Updated</span>
                    <span class="dv">{{ $assessment->updated_at->format('d M Y, h:i A') }}</span>
                </div>
            </div>
        </div>

        {{-- Delete — administrators only --}}
        @can('delete', $assessment)
        <div class="detail-card" style="border-color:#fee2e2;margin-bottom:0">
            <h6 style="color:#dc2626"><i class="bi bi-exclamation-triangle me-2"></i>Danger Zone</h6>
            <p style="font-size:.82rem;color:#64748b;margin-bottom:1rem">Deleting this record is permanent and cannot be undone.</p>
            <form method="POST" action="{{ route('assessments.destroy', $assessment) }}"
                  onsubmit="return confirm('Are you sure you want to permanently delete this assessment?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn w-100" style="background:#fee2e2;color:#dc2626;border:1.5px solid #fca5a5;border-radius:9px;font-weight:600;font-size:.85rem">
                    <i class="bi bi-trash me-1"></i> Delete Assessment
                </button>
            </form>
        </div>
        @endcan

    </div>
</div>

@endsection
