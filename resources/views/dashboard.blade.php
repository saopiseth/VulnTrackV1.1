@extends('layouts.app')
@section('title', 'Dashboard — ' . config('app.name'))

@section('content')

@if(session('error'))
<div class="alert d-flex align-items-center gap-2 mb-3" style="background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;border-radius:10px;font-size:.875rem">
    <i class="bi bi-shield-exclamation-fill"></i> {{ session('error') }}
</div>
@endif

{{-- Page Header --}}
<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h4>Dashboard</h4>
        <p>Welcome back, <strong>{{ auth()->user()->name ?? 'User' }}</strong>! Here's what's happening today.</p>
    </div>
    <button class="btn btn-sm" style="background:linear-gradient(135deg,#4f46e5,#3730a3);color:#fff;border-radius:9px;font-weight:600;padding:.5rem 1.1rem;border:none">
        <i class="bi bi-plus-lg me-1"></i> New Project
    </button>
</div>

{{-- Stat Widgets --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="stat-widget">
                <div class="sw-icon" style="background:#ede9fe">
                    <i class="bi bi-people-fill" style="color:#7c3aed"></i>
                </div>
                <div>
                    <div class="sw-label">Total Users</div>
                    <div class="sw-value">12,480</div>
                    <div class="sw-change up"><i class="bi bi-arrow-up-short"></i>8.2% this month</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="stat-widget">
                <div class="sw-icon" style="background:#cffafe">
                    <i class="bi bi-bar-chart-fill" style="color:#0891b2"></i>
                </div>
                <div>
                    <div class="sw-label">Revenue</div>
                    <div class="sw-value">$48,295</div>
                    <div class="sw-change up"><i class="bi bi-arrow-up-short"></i>12.5% this month</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="stat-widget">
                <div class="sw-icon" style="background:#d1fae5">
                    <i class="bi bi-check2-circle" style="color:#059669"></i>
                </div>
                <div>
                    <div class="sw-label">Projects Done</div>
                    <div class="sw-value">384</div>
                    <div class="sw-change up"><i class="bi bi-arrow-up-short"></i>5.1% this month</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="stat-widget">
                <div class="sw-icon" style="background:#fee2e2">
                    <i class="bi bi-exclamation-triangle-fill" style="color:#dc2626"></i>
                </div>
                <div>
                    <div class="sw-label">Open Tickets</div>
                    <div class="sw-value">27</div>
                    <div class="sw-change down"><i class="bi bi-arrow-down-short"></i>3.4% this month</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Content Row --}}
<div class="row g-3">

    {{-- Recent Activity --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3 px-4">
                <h6 class="mb-0 fw-700" style="font-weight:700;color:#0f172a">Recent Activity</h6>
                <a href="#" style="font-size:.82rem;color:#4f46e5;font-weight:600;text-decoration:none">View all</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size:.875rem">
                        <thead style="background:#f8fafc">
                            <tr>
                                <th class="px-4 py-3 border-0" style="color:#64748b;font-weight:600;font-size:.78rem;text-transform:uppercase;letter-spacing:.5px">User</th>
                                <th class="py-3 border-0" style="color:#64748b;font-weight:600;font-size:.78rem;text-transform:uppercase;letter-spacing:.5px">Action</th>
                                <th class="py-3 border-0" style="color:#64748b;font-weight:600;font-size:.78rem;text-transform:uppercase;letter-spacing:.5px">Status</th>
                                <th class="py-3 border-0" style="color:#64748b;font-weight:600;font-size:.78rem;text-transform:uppercase;letter-spacing:.5px">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                            $activities = [
                                ['John Smith','Submitted new project','Approved','2 min ago','#d1fae5','#059669'],
                                ['Maria Garcia','Updated user profile','Pending','15 min ago','#fef3c7','#d97706'],
                                ['David Lee','Deleted old records','Completed','1 hr ago','#ede9fe','#7c3aed'],
                                ['Sarah Kim','Uploaded documents','Approved','3 hrs ago','#d1fae5','#059669'],
                                ['Tom Wilson','Created new ticket','In Review','5 hrs ago','#cffafe','#0891b2'],
                            ];
                            @endphp
                            @foreach($activities as [$name, $action, $status, $time, $bgColor, $textColor])
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#4f46e5,#06b6d4);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.75rem;font-weight:700;flex-shrink:0">
                                            {{ strtoupper(substr($name, 0, 1)) }}
                                        </div>
                                        <span style="font-weight:600;color:#0f172a">{{ $name }}</span>
                                    </div>
                                </td>
                                <td class="py-3" style="color:#374151">{{ $action }}</td>
                                <td class="py-3">
                                    <span style="background:{{ $bgColor }};color:{{ $textColor }};padding:.2rem .65rem;border-radius:20px;font-size:.75rem;font-weight:600">
                                        {{ $status }}
                                    </span>
                                </td>
                                <td class="py-3" style="color:#94a3b8;font-size:.82rem">{{ $time }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body p-4">
                <h6 class="fw-700 mb-3" style="font-weight:700;color:#0f172a">Storage Usage</h6>
                @php
                $storage = [['Documents','74%','#4f46e5'],['Images','52%','#06b6d4'],['Videos','89%','#ef4444'],['Others','23%','#10b981']];
                @endphp
                @foreach($storage as [$label, $pct, $color])
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:.82rem;font-weight:600;color:#374151">{{ $label }}</span>
                        <span style="font-size:.82rem;color:#64748b">{{ $pct }}</span>
                    </div>
                    <div class="progress" style="height:6px;border-radius:10px;background:#f1f5f9">
                        <div class="progress-bar" style="width:{{ $pct }};background:{{ $color }};border-radius:10px"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="card">
            <div class="card-body p-4">
                <h6 class="fw-700 mb-3" style="font-weight:700;color:#0f172a">Quick Actions</h6>
                <div class="d-grid gap-2">
                    <button class="btn text-start d-flex align-items-center gap-2 py-2 px-3"
                        style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:9px;font-size:.85rem;font-weight:500;color:#374151">
                        <i class="bi bi-person-plus-fill" style="color:#4f46e5"></i> Add New User
                    </button>
                    <button class="btn text-start d-flex align-items-center gap-2 py-2 px-3"
                        style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:9px;font-size:.85rem;font-weight:500;color:#374151">
                        <i class="bi bi-folder-plus" style="color:#06b6d4"></i> Create Project
                    </button>
                    <button class="btn text-start d-flex align-items-center gap-2 py-2 px-3"
                        style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:9px;font-size:.85rem;font-weight:500;color:#374151">
                        <i class="bi bi-file-earmark-arrow-up" style="color:#10b981"></i> Upload File
                    </button>
                    <button class="btn text-start d-flex align-items-center gap-2 py-2 px-3"
                        style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:9px;font-size:.85rem;font-weight:500;color:#374151">
                        <i class="bi bi-graph-up-arrow" style="color:#f59e0b"></i> View Reports
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection
