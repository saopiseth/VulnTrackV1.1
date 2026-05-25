@extends('layouts.app')

@section('title', 'Hardening Standards')

@section('content')
<div class="page-header">
    <h4><i class="bi bi-journal-bookmark-fill me-2" style="color:var(--primary)"></i>Hardening Standards</h4>
    <p>Supported security hardening standards and frameworks</p>
</div>

<div class="row g-3">
    @foreach([
        ['CIS Benchmarks', 'bi-shield-lock-fill', '#3b82f6', 'Center for Internet Security configuration benchmarks for operating systems, applications, and network devices.', 'CIS Level 1, Level 2'],
        ['DISA STIG', 'bi-award-fill', '#8b5cf6', 'Defense Information Systems Agency Security Technical Implementation Guides for US Department of Defense systems.', 'CAT I, CAT II, CAT III'],
        ['NIST SP 800-53', 'bi-book-half', '#10b981', 'National Institute of Standards and Technology security and privacy controls for federal information systems.', 'Control Families'],
        ['ISO 27001', 'bi-patch-check-fill', '#f59e0b', 'International standard for information security management systems providing requirements and controls.', 'Annex A Controls'],
    ] as [$name, $icon, $color, $desc, $sub])
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body d-flex gap-3 align-items-start">
                <div style="width:48px;height:48px;border-radius:12px;background:{{ $color }}22;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="bi {{ $icon }}" style="font-size:1.3rem;color:{{ $color }}"></i>
                </div>
                <div>
                    <div class="fw-700" style="font-size:.95rem">{{ $name }}</div>
                    <div class="badge mb-2" style="background:{{ $color }}22;color:{{ $color }};font-size:.7rem">{{ $sub }}</div>
                    <p class="text-muted mb-0" style="font-size:.82rem">{{ $desc }}</p>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="alert alert-info mt-4 d-flex gap-2" style="font-size:.85rem">
    <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
    <div>
        Hardening standards mapping is detected automatically from the Nessus scan's plugin families and audit files.
        Nessus compliance audit plugins (e.g. CIS audit files) are automatically identified as Policy Compliance checks
        and mapped to Compliant, Non-Compliant, or Partially Compliant status.
    </div>
</div>
@endsection
