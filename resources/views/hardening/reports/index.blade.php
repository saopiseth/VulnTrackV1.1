@extends('layouts.app')

@section('title', 'Hardening Reports')

@section('content')
<div class="page-header">
    <h4><i class="bi bi-file-earmark-bar-graph-fill me-2" style="color:var(--primary)"></i>Hardening Reports</h4>
    <p>Export hardening assessment and verification reports</p>
</div>

@if($assessments->isEmpty())
<div class="alert alert-info d-flex gap-2" style="font-size:.875rem">
    <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
    <span>No completed assessments yet. <a href="{{ route('hardening.assessments.create') }}">Create an assessment</a> to generate reports.</span>
</div>
@else
<div class="card">
    <div class="card-body p-0">
        <div class="px-3 py-3" style="border-bottom:1px solid var(--border)">
            <h6 class="mb-0 fw-700" style="font-size:.85rem">Available Reports by Assessment</h6>
        </div>
        <table class="table table-hover mb-0" style="font-size:.875rem">
            <thead style="background:#f8fafc">
                <tr>
                    <th class="px-3 py-2">Assessment</th>
                    <th class="py-2">System / IP</th>
                    <th class="py-2">Date</th>
                    <th class="py-2">Findings</th>
                    <th class="py-2">Verifications</th>
                    <th class="py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($assessments as $a)
                <tr>
                    <td class="px-3 py-2">
                        <div class="fw-600">{{ $a->name }}</div>
                        <div style="font-size:.75rem;color:var(--text-muted)">{{ $a->environment }} &bull; {{ $a->criticality_level }}</div>
                    </td>
                    <td class="py-2">{{ $a->system_name }}<br><span style="font-size:.75rem;color:var(--text-muted)">{{ $a->ip_address }}</span></td>
                    <td class="py-2">{{ $a->assessment_date->format('d M Y') }}</td>
                    <td class="py-2">
                        <span class="text-danger fw-600">{{ $a->non_compliant_count }}</span> NC /
                        <span class="text-success fw-600">{{ $a->compliant_count }}</span> C
                    </td>
                    <td class="py-2">{{ $a->verifications_count }}</td>
                    <td class="py-2">
                        <a href="{{ route('hardening.assessments.show', $a) }}" class="btn btn-sm btn-outline-secondary py-1 px-2">
                            <i class="bi bi-eye me-1"></i>View
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($assessments->hasPages())
    <div class="card-footer bg-white d-flex justify-content-end">
        {{ $assessments->links() }}
    </div>
    @endif
</div>
@endif
@endsection
