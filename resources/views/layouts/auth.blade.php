<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'MyApp') }}</title>
    <link rel="icon" href="data:,">
    @php
        $themeHex = \App\Models\SiteSetting::get('theme_primary', '#98c20a');
        $themeHex = preg_match('/^#[0-9a-fA-F]{6}$/', $themeHex) ? $themeHex : '#98c20a';
        $h = ltrim($themeHex, '#');
        $tr = hexdec(substr($h,0,2)); $tg = hexdec(substr($h,2,2)); $tb = hexdec(substr($h,4,2));
        $dr = (int)($tr*0.78); $dg = (int)($tg*0.78); $db = (int)($tb*0.78);
        $lr = (int)($tr+(255-$tr)*0.55); $lg = (int)($tg+(255-$tg)*0.55); $lb = (int)($tb+(255-$tb)*0.55);
    @endphp
    <style>
        :root {
            --primary: rgb({{ $tr }},{{ $tg }},{{ $tb }});
            --primary-dark: rgb({{ $dr }},{{ $dg }},{{ $db }});
            --primary-light: rgb({{ $lr }},{{ $lg }},{{ $lb }});
            --primary-rgb: {{ $tr }},{{ $tg }},{{ $tb }};
        }
    </style>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --success: #10b981;
            --body-bg: #f8fafc;
            --card-bg: #ffffff;
            --text-muted: #64748b;
            --border: #e2e8f0;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--body-bg);
            min-height: 100vh;
            margin: 0;
        }

        /* ─── Left Panel ─── */
        .auth-left {
            background: linear-gradient(145deg, rgb(55,72,2) 0%, var(--primary-dark) 40%, var(--primary-dark) 100%);
            min-height: 100vh;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            padding: 2.5rem;
        }

        /* Decorative blobs */
        .auth-left::before {
            content: '';
            position: absolute;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(200,225,120,.25) 0%, transparent 70%);
            top: -100px; left: -100px;
            border-radius: 50%;
        }
        .auth-left::after {
            content: '';
            position: absolute;
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(var(--primary-rgb),.2) 0%, transparent 70%);
            bottom: -60px; right: -60px;
            border-radius: 50%;
        }

        .auth-left .blob-mid {
            position: absolute;
            width: 200px; height: 200px;
            background: radial-gradient(circle, rgba(180,220,50,.15) 0%, transparent 70%);
            bottom: 35%; left: 60%;
            border-radius: 50%;
        }

        .auth-brand {
            position: relative; z-index: 2;
            display: flex; align-items: center; gap: .75rem;
        }
        .auth-brand .brand-icon {
            width: 44px; height: 44px;
            background: rgba(255,255,255,.15);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; color: #fff; font-weight: 700;
            box-shadow: 0 8px 20px rgba(0,0,0,.15);
            overflow: hidden; padding: 4px;
        }
        .auth-brand .brand-name {
            color: #fff; font-size: 1.35rem; font-weight: 700; letter-spacing: -.3px;
        }

        .auth-hero {
            position: relative; z-index: 2;
            flex: 1;
            display: flex; flex-direction: column; justify-content: center;
            padding: 2rem 0;
        }
        .auth-hero h1 {
            color: #fff; font-size: 2.2rem; font-weight: 800;
            line-height: 1.2; letter-spacing: -.5px; margin-bottom: 1rem;
        }
        .auth-hero h1 span {
            background: linear-gradient(90deg, rgb(220,240,130), rgb(180,220,50));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .auth-hero p {
            color: rgba(255,255,255,.65); font-size: .95rem; line-height: 1.7; max-width: 320px;
        }

        /* Stat cards */
        .stat-cards { margin-top: 2.5rem; display: flex; flex-direction: column; gap: .85rem; }
        .stat-card {
            background: rgba(255,255,255,.07);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 14px;
            padding: .9rem 1.1rem;
            display: flex; align-items: center; gap: .9rem;
            max-width: 280px;
        }
        .stat-card .stat-icon {
            width: 38px; height: 38px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; flex-shrink: 0;
        }
        .stat-card .stat-label { color: rgba(255,255,255,.55); font-size: .72rem; font-weight: 500; text-transform: uppercase; letter-spacing: .5px; }
        .stat-card .stat-value { color: #fff; font-size: 1.05rem; font-weight: 700; margin-top: 1px; }

        /* Floating dot grid */
        .dot-grid {
            position: absolute; bottom: 80px; right: 20px;
            display: grid; grid-template-columns: repeat(6,1fr); gap: 8px; z-index: 1; opacity: .25;
        }
        .dot-grid span {
            width: 4px; height: 4px; background: rgba(255,255,255,.7); border-radius: 50%; display: block;
        }

        /* ─── Right Panel ─── */
        .auth-right {
            min-height: 100vh;
            background: #fff;
            display: flex; align-items: center; justify-content: center;
            padding: 2.5rem 2rem;
        }
        .auth-form-wrap {
            width: 100%; max-width: 420px;
        }
        .auth-form-wrap .auth-title {
            font-size: 1.65rem; font-weight: 800; color: #0f172a; letter-spacing: -.4px;
        }
        .auth-form-wrap .auth-subtitle {
            color: var(--text-muted); font-size: .9rem; margin-top: .35rem;
        }

        /* Form Controls */
        .form-label { font-weight: 600; font-size: .83rem; color: #374151; margin-bottom: .4rem; }
        .input-group-text {
            background: #f8fafc; border-color: var(--border); color: var(--text-muted);
        }
        .form-control {
            border-color: var(--border); font-size: .9rem; padding: .6rem .85rem;
            color: #1e293b; background: #fff;
            transition: border-color .2s, box-shadow .2s;
        }
        .form-control:focus {
            border-color: var(--primary); box-shadow: 0 0 0 3px rgba(var(--primary-rgb),.12);
        }
        .input-group .form-control { border-left: 0; }
        .input-group .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(var(--primary-rgb),.12); }
        .input-group:focus-within .input-group-text { border-color: var(--primary); }

        /* Primary Button */
        .btn-primary-custom {
            background: var(--primary);
            border: none; color: #fff; font-weight: 600; font-size: .9rem;
            padding: .72rem 1.5rem; border-radius: 10px; width: 100%;
            transition: all .2s; letter-spacing: .2px;
            box-shadow: 0 4px 14px rgba(var(--primary-rgb),.4);
        }
        .btn-primary-custom:hover {
            transform: translateY(-1px); box-shadow: 0 6px 20px rgba(var(--primary-rgb),.5);
            background: rgb(136,174,9); color: #fff;
        }
        .btn-primary-custom:active { transform: translateY(0); }

        /* Social Buttons */
        .btn-social {
            border: 1.5px solid var(--border); background: #fff; color: #374151;
            font-size: .85rem; font-weight: 500; padding: .55rem .9rem;
            border-radius: 9px; display: flex; align-items: center; justify-content: center;
            gap: .5rem; transition: all .2s;
        }
        .btn-social:hover { background: #f8fafc; border-color: #cbd5e1; color: #0f172a; }

        /* Divider */
        .auth-divider {
            display: flex; align-items: center; gap: .9rem; margin: 1.4rem 0;
            color: var(--text-muted); font-size: .8rem; font-weight: 500;
        }
        .auth-divider::before, .auth-divider::after {
            content: ''; flex: 1; height: 1px; background: var(--border);
        }

        /* Misc */
        .form-check-input:checked { background-color: var(--primary); border-color: var(--primary); }
        .link-primary-custom { color: var(--primary-dark); font-weight: 600; text-decoration: none; }
        .link-primary-custom:hover { color: var(--primary-dark); text-decoration: underline; }
        .toggle-password { cursor: pointer; background: #f8fafc; border-color: var(--border); color: var(--text-muted); }
        .toggle-password:hover { color: var(--primary); }
        .alert { border-radius: 10px; font-size: .88rem; }

        /* Responsive */
        @media (max-width: 991.98px) {
            .auth-left { min-height: auto; padding: 2rem; }
            .auth-left::before, .auth-left::after, .dot-grid { display: none; }
            .auth-hero h1 { font-size: 1.6rem; }
            .auth-right { min-height: auto; padding: 2rem 1.25rem; }
        }
    </style>
</head>
<body>
    @yield('content')

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
