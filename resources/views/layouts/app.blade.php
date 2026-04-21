<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Security Assessment' }}</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: rgb(152,194,10);
            --primary-dark: rgb(118,151,7);
            --primary-light: rgb(200,225,120);
            --sidebar-bg: #ffffff;
            --sidebar-width: 260px;
            --topbar-h: 64px;
            --body-bg: #f8fafc;
            --card-bg: #ffffff;
            --text-muted: #64748b;
            --border: #e2e8f0;
        }

        body { font-family: 'Inter', sans-serif; background: var(--body-bg); margin: 0; }

        /* ── Sidebar ── */
        .sidebar {
            position: fixed; top: 0; left: 0; bottom: 0;
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
            z-index: 1000; overflow-y: auto;
            transition: transform .3s ease;
        }
        .sidebar-brand {
            height: var(--topbar-h);
            padding: 0 1.5rem;
            display: flex; align-items: center; gap: .75rem;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }
        .sidebar-brand .brand-icon {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, rgb(152,194,10), rgb(100,140,5));
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; color: #fff; font-weight: 700;
        }
        .sidebar-brand .brand-name { color: #0f172a; font-size: 1.1rem; font-weight: 700; }

        .sidebar-nav { padding: 1rem .75rem; flex: 1; }
        .sidebar-label {
            color: #94a3b8; font-size: .7rem; font-weight: 600;
            letter-spacing: 1px; text-transform: uppercase;
            padding: .75rem .75rem .35rem;
        }
        .nav-item a {
            display: flex; align-items: center; gap: .75rem;
            padding: .6rem .85rem; border-radius: 10px;
            color: #64748b; font-size: .875rem; font-weight: 500;
            text-decoration: none; transition: all .2s; margin-bottom: 2px;
        }
        .nav-item a:hover { background: rgb(240,248,210); color: rgb(118,151,7); }
        .nav-item a.active {
            background: rgb(232,244,195); color: rgb(118,151,7); font-weight: 600;
            border-left: 3px solid rgb(152,194,10);
        }
        .nav-item a.active i { color: rgb(152,194,10); }
        .nav-item a i { font-size: 1.05rem; width: 20px; text-align: center; color: #94a3b8; }
        .nav-item a:hover i { color: rgb(152,194,10); }
        .nav-badge {
            margin-left: auto; background: #ef4444; color: #fff;
            font-size: .68rem; font-weight: 700; padding: .15rem .45rem;
            border-radius: 20px;
        }

        .sidebar-footer {
            padding: 1rem 1.25rem;
            border-top: 1px solid var(--border);
        }
        .sidebar-user {
            display: flex; align-items: center; gap: .75rem;
            padding: .6rem .5rem; border-radius: 10px; cursor: pointer;
            transition: background .2s;
        }
        .sidebar-user:hover { background: rgb(240,248,210); }
        .sidebar-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, rgb(152,194,10), rgb(100,140,5));
            display: flex; align-items: center; justify-content: center;
            font-size: .85rem; font-weight: 700; color: #fff; flex-shrink: 0;
        }
        .sidebar-user-name { color: #0f172a; font-size: .85rem; font-weight: 600; }
        .sidebar-user-role { color: #94a3b8; font-size: .75rem; }

        /* ── Main ── */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex; flex-direction: column;
        }

        /* ── Topbar ── */
        .topbar {
            height: var(--topbar-h);
            background: #fff;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; padding: 0 1.75rem;
            position: sticky; top: 0; z-index: 100;
            gap: 1rem;
        }
        .topbar-search {
            flex: 1; max-width: 340px;
            background: var(--body-bg); border: 1.5px solid var(--border);
            border-radius: 10px; display: flex; align-items: center;
            padding: .45rem .85rem; gap: .5rem;
        }
        .topbar-search input {
            border: none; background: transparent; outline: none;
            font-size: .875rem; color: #374151; width: 100%;
        }
        .topbar-search input::placeholder { color: var(--text-muted); }
        .topbar-search i { color: var(--text-muted); }
        .topbar-right { display: flex; align-items: center; gap: .75rem; margin-left: auto; }
        .topbar-icon {
            width: 38px; height: 38px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            background: var(--body-bg); border: 1.5px solid var(--border);
            color: var(--text-muted); cursor: pointer; position: relative;
            transition: all .2s;
        }
        .topbar-icon:hover { background: rgb(240,248,210); color: rgb(118,151,7); border-color: rgb(200,225,120); }
        .notif-dot {
            position: absolute; top: 6px; right: 6px;
            width: 8px; height: 8px; border-radius: 50%; background: #ef4444;
            border: 2px solid #fff;
        }

        /* ── Page content ── */
        .page-content { padding: 1.75rem; flex: 1; }
        .page-header { margin-bottom: 1.5rem; }
        .page-header h4 { font-size: 1.35rem; font-weight: 700; color: #0f172a; margin: 0; }
        .page-header p { color: var(--text-muted); font-size: .875rem; margin: .25rem 0 0; }
        .breadcrumb { font-size: .8rem; }
        .breadcrumb-item a { color: rgb(118,151,7); text-decoration: none; }

        /* ── Cards ── */
        .card { border: 1px solid var(--border); border-radius: 14px; box-shadow: 0 1px 4px rgba(0,0,0,.04); }
        .stat-widget {
            border-radius: 14px; padding: 1.35rem;
            display: flex; align-items: flex-start; gap: 1rem;
        }
        .stat-widget .sw-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; flex-shrink: 0;
        }
        .stat-widget .sw-label { color: var(--text-muted); font-size: .8rem; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
        .stat-widget .sw-value { font-size: 1.65rem; font-weight: 800; color: #0f172a; line-height: 1.2; }
        .stat-widget .sw-change { font-size: .78rem; font-weight: 600; }
        .sw-change.up { color: #10b981; }
        .sw-change.down { color: #ef4444; }

        /* Responsive */
        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

    {{-- Sidebar --}}
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon" style="background:none;padding:0;overflow:hidden">
                <img src="{{ asset('favicon.ico') }}" alt="Wing Bank" style="width:38px;height:38px;object-fit:contain;border-radius:10px">
            </div>
            <span class="brand-name">Security Assessment</span>
        </div>

        <nav class="sidebar-nav">
            <div class="sidebar-label">Main</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="bi bi-grid-1x2-fill"></i> Dashboard
                    </a>
                </li>
                @can('viewAny', App\Models\User::class)
                <li class="nav-item">
                    <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <i class="bi bi-people-fill"></i> Users
                    </a>
                </li>
                @endcan
            </ul>

            <div class="sidebar-label mt-2">Vulnerability Management</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('assessment-scope.index') }}" class="{{ request()->routeIs('assessment-scope.*') ? 'active' : '' }}">
                        <i class="bi bi-diagram-3-fill"></i> Assessment Scope
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('vuln-assessments.index') }}" class="{{ request()->routeIs('vuln-assessments.*') ? 'active' : '' }}">
                        <i class="bi bi-bug-fill"></i> Vulnerability Tracking
                    </a>
                </li>
            </ul>

            <div class="sidebar-label mt-2">Account</div>
            <ul class="list-unstyled mb-0">
                <li class="nav-item">
                    <a href="{{ route('account.profile') }}" class="{{ request()->routeIs('account.profile') ? 'active' : '' }}">
                        <i class="bi bi-person-fill"></i> Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('account.settings') }}" class="{{ request()->routeIs('account.settings') ? 'active' : '' }}">
                        <i class="bi bi-gear-fill"></i> Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('logout') }}"
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="bi bi-box-arrow-left"></i> Logout
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                </li>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-avatar">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
                <div>
                    <div class="sidebar-user-name">{{ auth()->user()->name ?? 'User' }}</div>
                    <div class="sidebar-user-role" style="display:flex;align-items:center;gap:.4rem;margin-top:2px">
                        <span style="background:rgb(232,244,195);color:rgb(118,151,7);font-size:.65rem;font-weight:700;padding:.1rem .45rem;border-radius:5px;text-transform:uppercase;letter-spacing:.4px">
                            {{ auth()->user()->role }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    {{-- Main --}}
    <div class="main-content">

        {{-- Topbar --}}
        <header class="topbar">
            <button class="d-lg-none btn p-1 me-1" id="sidebarToggle" style="border:none;background:none;color:#64748b">
                <i class="bi bi-list" style="font-size:1.4rem"></i>
            </button>
            <div class="topbar-search d-none d-md-flex">
                <i class="bi bi-search"></i>
                <input type="text" placeholder="Search anything...">
            </div>
            <div class="topbar-right">
                <div class="topbar-icon">
                    <i class="bi bi-bell"></i>
                    <span class="notif-dot"></span>
                </div>
                <div class="topbar-icon">
                    <i class="bi bi-chat-dots"></i>
                </div>
                <div class="topbar-icon">
                    <i class="bi bi-question-circle"></i>
                </div>
                <div class="sidebar-avatar" style="cursor:pointer">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
            </div>
        </header>

        {{-- Page Content --}}
        <main class="page-content">
            @yield('content')
        </main>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile sidebar toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
        }
    </script>
    @stack('scripts')
</body>
</html>
