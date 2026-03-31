<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin Panel')</title>
    <style>
        :root {
            --bg: #f6efe5;
            --panel: #ffffff;
            --panel-soft: #fff9f1;
            --text: #1f2430;
            --muted: #6f6a62;
            --line: #ead9c6;
            --brand: #d96c2f;
            --brand-soft: #ffe0c9;
            --brand-dark: #9f3f15;
            --accent: #17806d;
            --accent-soft: #d9f6ef;
            --danger: #b42318;
            --shadow: 0 20px 60px rgba(110,63,20,0.12);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(255,231,200,0.95), transparent 30%),
                radial-gradient(circle at bottom right, rgba(217,108,47,0.10), transparent 24%),
                linear-gradient(180deg, #fff8ef 0%, var(--bg) 100%);
            color: var(--text);
            overflow-x:hidden;
        }
        a { color: inherit; text-decoration: none; }
        .admin-shell { display: grid; grid-template-columns: 240px 1fr; min-height: 100vh; position:relative; }
        .sidebar {
            background:
                linear-gradient(180deg, rgba(73,33,13,0.97) 0%, rgba(35,20,14,0.98) 100%);
            color: #f9e8d8;
            padding: 24px 18px;
            border-right: 1px solid rgba(255,255,255,0.07);
        }
        .brand { font-size: 20px; font-weight: 800; margin-bottom: 24px; color: #fff5eb; letter-spacing: 0.02em; }
        .nav-link {
            display: block;
            padding: 11px 13px;
            border-radius: 12px;
            margin-bottom: 8px;
            color: #f3d8c0;
            transition: all .18s ease;
        }
        .nav-link.active, .nav-link:hover {
            background: linear-gradient(90deg, rgba(217,108,47,0.24), rgba(255,255,255,0.08));
            color: #fff;
            transform: translateX(2px);
        }
        .content { padding: 24px; }
        .menu-toggle {
            display:none;
            border:1px solid var(--line);
            background:linear-gradient(135deg, #fff5ea 0%, #f8e3cf 100%);
            color:var(--brand-dark);
            width:44px;
            height:44px;
            border-radius:12px;
            cursor:pointer;
            font-size:20px;
            font-weight:900;
            flex:0 0 auto;
        }
        .topbar-main {
            display:flex;
            align-items:center;
            gap:12px;
        }
        .sidebar-backdrop {
            display:none;
            position:fixed;
            inset:0;
            background:rgba(15,23,42,0.48);
            z-index:69;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding: 18px 20px;
            border: 1px solid rgba(234,217,198,0.95);
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(255,253,249,0.92), rgba(255,244,231,0.92));
            box-shadow: var(--shadow);
        }
        .panel {
            background: linear-gradient(180deg, #ffffff 0%, var(--panel-soft) 100%);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 18px;
            box-shadow: var(--shadow);
        }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card {
            background: linear-gradient(180deg, #ffffff 0%, #fff7ee 100%);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 18px;
            box-shadow: var(--shadow);
        }
        .stat-label { color: var(--muted); font-size: 13px; margin-bottom: 8px; }
        .stat-value { font-size: 28px; font-weight: 800; color: var(--brand-dark); }
        .table-wrap { overflow-x: auto; }
        .responsive-table { overflow-x:visible; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px 10px; border-bottom: 1px solid rgba(234,217,198,0.9); vertical-align: top; }
        th { color: var(--muted); font-size: 13px; font-weight: 700; background: rgba(255,246,236,0.75); }
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #fffdfa;
            color: var(--text);
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #ef8a43;
            box-shadow: 0 0 0 4px rgba(239,138,67,0.12);
        }
        label { display: block; font-size: 13px; color: var(--muted); margin-bottom: 6px; }
        .badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--brand-soft), #fff0e2);
            color: var(--brand-dark);
            font-size: 12px;
            font-weight: 800;
            border: 1px solid rgba(217,108,47,0.14);
        }
        .badge.off { background: #fee4e2; color: var(--danger); }
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .muted { color: var(--muted); }
        .btn {
            border: 0;
            background: linear-gradient(135deg, var(--brand) 0%, #ef8a43 100%);
            color: #fff;
            padding: 10px 14px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            box-shadow: 0 10px 26px rgba(217,108,47,0.22);
        }
        .btn-secondary {
            background: linear-gradient(135deg, #fff5ea 0%, #f8e3cf 100%);
            color: var(--brand-dark);
            border: 1px solid var(--line);
            box-shadow: none;
        }
        .stack { display: grid; gap: 16px; }
        .flash { background: #ecfdf3; border: 1px solid #abefc6; color: #067647; padding: 12px 14px; border-radius: 10px; margin-bottom: 18px; }
        .error-list { background: #fee4e2; border: 1px solid #fecdca; color: #b42318; padding: 12px 14px; border-radius: 10px; margin-bottom: 18px; }
        .text-link { color: var(--accent); font-weight: 800; }
        .split-2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .split-main-aside { display:grid; grid-template-columns:1.15fr 0.85fr; gap:16px; }
        .split-wide-narrow { display:grid; grid-template-columns:0.95fr 1.05fr; gap:16px; }
        .mobile-actions { display:flex; gap:10px; flex-wrap:wrap; }
        .highlight-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .highlight-card { padding: 20px; border-radius: 20px; border: 1px solid var(--line); box-shadow: var(--shadow); }
        .live-card { background: linear-gradient(135deg, #fff1e2 0%, #ffe3c2 52%, #fffdf7 100%); }
        .running-card { background: linear-gradient(135deg, #e7fff8 0%, #d8f6ef 52%, #fffdf7 100%); }
        .live-callout { min-width:220px; padding:16px; border-radius:18px; background:rgba(255,255,255,0.14); border:1px solid rgba(255,255,255,0.18); backdrop-filter:blur(6px); }
        .highlight-top { display:flex; justify-content:space-between; gap:10px; align-items:center; margin-bottom:14px; }
        .highlight-value { font-size: 42px; font-weight: 900; color: var(--brand-dark); line-height: 1; margin-bottom: 8px; }
        .highlight-label { font-size: 16px; font-weight: 800; margin-bottom: 6px; }
        .highlight-sub { color: var(--muted); line-height: 1.6; }
        .live-pill, .running-pill {
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:7px 12px;
            border-radius:999px;
            font-size:12px;
            font-weight:900;
            letter-spacing:0.08em;
            text-transform:uppercase;
            width:max-content;
        }
        .live-pill {
            background:linear-gradient(135deg, #ff6b35, #ff944d);
            color:#fff;
            box-shadow:0 0 0 0 rgba(255,107,53,0.42);
            animation:livePulse 1.8s infinite;
        }
        .running-pill {
            background:linear-gradient(135deg, #17806d, #26a98f);
            color:#fff;
            box-shadow:0 0 0 0 rgba(23,128,109,0.32);
            animation:livePulse 1.8s infinite;
        }
        .live-pill::before, .running-pill::before {
            content:"";
            width:8px;
            height:8px;
            border-radius:999px;
            background:rgba(255,255,255,0.9);
            display:inline-block;
        }
        @keyframes livePulse {
            0% { transform:scale(1); box-shadow:0 0 0 0 rgba(255,107,53,0.30); }
            70% { transform:scale(1.02); box-shadow:0 0 0 14px rgba(255,107,53,0); }
            100% { transform:scale(1); box-shadow:0 0 0 0 rgba(255,107,53,0); }
        }
        .modal-shell { position: fixed; inset: 0; display: none; z-index: 60; }
        .modal-shell.is-open { display: block; }
        .modal-backdrop { position: absolute; inset: 0; background: rgba(15,23,42,0.6); }
        .modal-card {
            position: relative;
            z-index: 1;
            width: min(1100px, calc(100vw - 32px));
            max-height: calc(100vh - 32px);
            overflow: auto;
            margin: 16px auto;
            background: linear-gradient(180deg, #fffdfa 0%, #fff5ea 100%);
            border-radius: 20px;
            padding: 20px;
            border: 1px solid var(--line);
            box-shadow: 0 24px 80px rgba(69,36,14,0.28);
        }
        .modal-head { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid rgba(234,217,198,0.9); }
        .modal-close { border: 0; background: #fff0e0; color: var(--brand-dark); width: 36px; height: 36px; border-radius: 999px; cursor: pointer; font-size: 24px; line-height: 1; }
        @media (max-width: 900px) {
            .admin-shell { grid-template-columns: 1fr; }
            .sidebar {
                position:fixed;
                top:0;
                left:-290px;
                width:270px;
                height:100vh;
                z-index:70;
                transition:left .24s ease;
                overflow-y:auto;
                padding-bottom:24px;
            }
            .admin-shell.sidebar-open .sidebar { left:0; }
            .admin-shell.sidebar-open .sidebar-backdrop { display:block; }
            .menu-toggle { display:inline-flex; align-items:center; justify-content:center; }
            .topbar, .header-row { flex-direction:column; align-items:flex-start; }
            .content { padding:16px; }
            .split-2, .split-main-aside, .split-wide-narrow, .highlight-grid { grid-template-columns: 1fr; }
            .live-callout { min-width:0; width:100%; }
            .modal-card { width:calc(100vw - 20px); margin:10px auto; padding:16px; }
        }
        @media (max-width: 600px) {
            .content { padding:12px; }
            .topbar { padding:14px; }
            .panel, .stat-card { padding:14px; border-radius:16px; }
            .stat-value { font-size:24px; }
            .mobile-actions .btn, .mobile-actions .btn-secondary { width:100%; text-align:center; }
            .table-wrap { margin:0 -4px; }
        }
        @media (max-width:700px) {
            .responsive-table table,
            .responsive-table thead,
            .responsive-table tbody,
            .responsive-table tr,
            .responsive-table th,
            .responsive-table td {
                display:block;
                width:100%;
            }
            .responsive-table thead {
                display:none;
            }
            .responsive-table tbody {
                display:grid;
                gap:12px;
            }
            .responsive-table tr {
                padding:14px;
                border:1px solid var(--line);
                border-radius:16px;
                background:linear-gradient(180deg, #fffaf4 0%, #fff 100%);
                box-shadow:var(--shadow);
            }
            .responsive-table td {
                padding:8px 0;
                border-bottom:1px dashed rgba(234,217,198,0.9);
                display:grid;
                grid-template-columns:minmax(110px, 42%) 1fr;
                gap:10px;
                align-items:flex-start;
                white-space:normal !important;
            }
            .responsive-table td:last-child {
                border-bottom:0;
                padding-bottom:0;
            }
            .responsive-table td::before {
                content:attr(data-label);
                font-size:12px;
                font-weight:800;
                color:var(--brand-dark);
                text-transform:uppercase;
                letter-spacing:0.04em;
            }
            .responsive-table td[colspan] {
                display:block;
            }
            .responsive-table td[colspan]::before {
                content:none;
            }
        }
    </style>
</head>
<body>
@php($errors = $errors ?? new \Illuminate\Support\ViewErrorBag())
<div class="admin-shell" id="adminPanelShell">
    <div class="sidebar-backdrop" data-sidebar-close="adminPanelShell"></div>
    <aside class="sidebar">
        <div class="brand">Gaming Admin</div>
        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
        <a class="nav-link {{ request()->routeIs('admin.games.*') ? 'active' : '' }}" href="{{ route('admin.games.index') }}">Games</a>
        <a class="nav-link {{ request()->routeIs('admin.tournaments.index') || request()->routeIs('admin.tournaments.create') ? 'active' : '' }}" href="{{ route('admin.tournaments.index') }}">Tournaments</a>
        <a class="nav-link {{ request()->routeIs('admin.tournaments.matches') ? 'active' : '' }}" href="{{ route('admin.tournaments.matches') }}" style="padding-left:24px;font-size:13px;">↳ Match Monitor</a>
        <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">Users</a>
        <a class="nav-link {{ request()->routeIs('admin.support.*') ? 'active' : '' }}" href="{{ route('admin.support.index') }}">Support Tickets</a>
        <a class="nav-link {{ request()->routeIs('admin.wallet-transactions.*') ? 'active' : '' }}" href="{{ route('admin.wallet-transactions.index') }}">Wallet Transactions</a>
        <a class="nav-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}" href="{{ route('admin.audit-logs.index') }}">Audit Logs</a>
    </aside>
    <main class="content">
        <div class="topbar">
            <div class="topbar-main">
                <button type="button" class="menu-toggle" data-sidebar-open="adminPanelShell">☰</button>
                <div>
                    <div style="font-size: 24px; font-weight: 700;">@yield('heading', 'Admin')</div>
                    <div class="muted">@yield('subheading', 'Operational control panel')</div>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="btn btn-secondary">Logout</button>
            </form>
        </div>

        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="error-list">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @yield('content')
    </main>
</div>
@stack('scripts')
<script>
document.querySelectorAll('[data-sidebar-open]').forEach(function (button) {
    button.addEventListener('click', function () {
        document.getElementById(button.getAttribute('data-sidebar-open'))?.classList.add('sidebar-open');
    });
});
document.querySelectorAll('[data-sidebar-close]').forEach(function (button) {
    button.addEventListener('click', function () {
        document.getElementById(button.getAttribute('data-sidebar-close'))?.classList.remove('sidebar-open');
    });
});
document.querySelectorAll('.sidebar .nav-link').forEach(function (link) {
    link.addEventListener('click', function () {
        document.getElementById('adminPanelShell')?.classList.remove('sidebar-open');
    });
});
</script>
</body>
</html>
