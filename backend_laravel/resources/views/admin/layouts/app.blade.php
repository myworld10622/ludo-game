<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin Panel')</title>
    <style>
        :root {
            --bg: #f4f6f8;
            --panel: #ffffff;
            --text: #15202b;
            --muted: #5b6670;
            --line: #d9e1e7;
            --brand: #0f766e;
            --brand-soft: #d8f3ef;
            --danger: #b42318;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Segoe UI", sans-serif; background: var(--bg); color: var(--text); }
        a { color: inherit; text-decoration: none; }
        .admin-shell { display: grid; grid-template-columns: 240px 1fr; min-height: 100vh; }
        .sidebar { background: #0f172a; color: #e2e8f0; padding: 24px 18px; }
        .brand { font-size: 20px; font-weight: 700; margin-bottom: 24px; }
        .nav-link { display: block; padding: 10px 12px; border-radius: 10px; margin-bottom: 8px; color: #cbd5e1; }
        .nav-link.active, .nav-link:hover { background: rgba(255,255,255,0.08); color: #fff; }
        .content { padding: 24px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .panel { background: var(--panel); border: 1px solid var(--line); border-radius: 14px; padding: 18px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: var(--panel); border: 1px solid var(--line); border-radius: 14px; padding: 18px; }
        .stat-label { color: var(--muted); font-size: 13px; margin-bottom: 8px; }
        .stat-value { font-size: 28px; font-weight: 700; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px 10px; border-bottom: 1px solid var(--line); vertical-align: top; }
        th { color: var(--muted); font-size: 13px; font-weight: 600; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 999px; background: var(--brand-soft); color: var(--brand); font-size: 12px; font-weight: 700; }
        .badge.off { background: #fee4e2; color: var(--danger); }
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .muted { color: var(--muted); }
        .btn { border: 0; background: var(--brand); color: #fff; padding: 10px 14px; border-radius: 10px; cursor: pointer; }
        .btn-secondary { background: #e2e8f0; color: #0f172a; }
        .stack { display: grid; gap: 16px; }
        .flash { background: #ecfdf3; border: 1px solid #abefc6; color: #067647; padding: 12px 14px; border-radius: 10px; margin-bottom: 18px; }
        .error-list { background: #fee4e2; border: 1px solid #fecdca; color: #b42318; padding: 12px 14px; border-radius: 10px; margin-bottom: 18px; }
        @media (max-width: 900px) {
            .admin-shell { grid-template-columns: 1fr; }
            .sidebar { padding-bottom: 0; }
        }
    </style>
</head>
<body>
@php($errors = $errors ?? new \Illuminate\Support\ViewErrorBag())
<div class="admin-shell">
    <aside class="sidebar">
        <div class="brand">Gaming Admin</div>
        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
        <a class="nav-link {{ request()->routeIs('admin.games.*') ? 'active' : '' }}" href="{{ route('admin.games.index') }}">Games</a>
        <a class="nav-link {{ request()->routeIs('admin.tournaments.*') ? 'active' : '' }}" href="{{ route('admin.tournaments.index') }}">Tournaments</a>
        <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">Users</a>
        <a class="nav-link {{ request()->routeIs('admin.wallet-transactions.*') ? 'active' : '' }}" href="{{ route('admin.wallet-transactions.index') }}">Wallet Transactions</a>
        <a class="nav-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}" href="{{ route('admin.audit-logs.index') }}">Audit Logs</a>
    </aside>
    <main class="content">
        <div class="topbar">
            <div>
                <div style="font-size: 24px; font-weight: 700;">@yield('heading', 'Admin')</div>
                <div class="muted">@yield('subheading', 'Operational control panel')</div>
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
</body>
</html>
