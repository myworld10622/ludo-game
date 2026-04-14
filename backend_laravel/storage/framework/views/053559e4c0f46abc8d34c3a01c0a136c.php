<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $__env->yieldContent('title', 'Admin Panel'); ?> — RoxLudo</title>
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Exo+2:wght@300;400;600;800;900&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #0A0A14;
            --card: #12121F;
            --card2: #1A1A2E;
            --panel: #12121F;
            --panel-soft: #1A1A2E;
            --line: rgba(255,215,0,0.14);
            --line-dim: rgba(255,255,255,0.07);
            --text: #F0F0FF;
            --muted: #8888AA;
            --gold: #FFD700;
            --gold-dark: #CC9900;
            --gold-soft: rgba(255,215,0,0.12);
            --green: #06D6A0;
            --red: #E63946;
            --blue: #1A6BFF;
            --brand: #FFD700;
            --brand-soft: rgba(255,215,0,0.12);
            --brand-dark: #CC9900;
            --accent: #06D6A0;
            --accent-soft: rgba(6,214,160,0.12);
            --danger: #E63946;
            --shadow: 0 20px 60px rgba(0,0,0,0.6);
        }

        body {
            font-family: 'Exo 2', sans-serif;
            background: var(--bg);
            color: var(--text);
            overflow-x: hidden;
            min-height: 100vh;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,215,0,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,215,0,0.02) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
            z-index: 0;
        }

        a { color: var(--gold); text-decoration: none; }
        a:hover { opacity: .85; }

        /* ── LAYOUT ── */
        .admin-shell {
            display: grid;
            grid-template-columns: 240px 1fr;
            min-height: 100vh;
            position: relative;
            z-index: 1;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            background: linear-gradient(180deg, #0D0D1A 0%, #090910 100%);
            color: #D0D0EE;
            padding: 20px 14px 32px;
            border-right: 1px solid var(--line-dim);
            position: relative;
            overflow-y: auto;
        }
        .sidebar::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--gold), #FF9500, transparent);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 22px;
            padding: 0 6px;
        }
        .brand-icon {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, var(--gold), var(--red));
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
            filter: drop-shadow(0 0 8px rgba(255,215,0,0.35));
        }
        .brand-label {
            font-family: 'Orbitron', sans-serif;
            font-size: 13px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--gold), #FF6B6B);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: 0.5px;
        }
        .brand-sub {
            font-size: 10px;
            color: rgba(136,136,170,0.7);
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-top: 1px;
        }

        .nav-section {
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: rgba(136,136,170,0.5);
            padding: 0 8px;
            margin: 16px 0 5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 10px;
            border-radius: 9px;
            margin-bottom: 2px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 600;
            transition: all .15s ease;
            border: 1px solid transparent;
        }
        .nav-link.sub {
            padding-left: 26px;
            font-size: 12px;
            color: rgba(136,136,170,0.7);
        }
        .nav-link .ni { font-size: 13px; flex-shrink: 0; }
        .nav-link.active, .nav-link:hover {
            background: var(--gold-soft);
            color: var(--gold);
            border-color: rgba(255,215,0,0.15);
        }
        .nav-link.sub.active, .nav-link.sub:hover {
            color: rgba(255,215,0,0.8);
            background: rgba(255,215,0,0.06);
        }

        /* ── CONTENT ── */
        .content { padding: 24px; overflow-x: hidden; }

        /* ── MOBILE TOGGLE ── */
        .menu-toggle {
            display: none;
            border: 1px solid var(--line);
            background: var(--gold-soft);
            color: var(--gold);
            width: 40px; height: 40px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 18px;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
        }
        .topbar-main { display: flex; align-items: center; gap: 12px; }

        /* ── SIDEBAR BACKDROP ── */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.75);
            z-index: 69;
            backdrop-filter: blur(4px);
        }

        /* ── TOPBAR ── */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding: 16px 20px;
            border: 1px solid var(--line-dim);
            border-radius: 16px;
            background: var(--card);
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            gap: 16px;
        }
        .topbar::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,215,0,0.3), transparent);
        }
        .topbar-heading {
            font-family: 'Orbitron', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: var(--text);
            letter-spacing: 0.5px;
        }
        .topbar-sub { font-size: 12px; color: var(--muted); margin-top: 2px; }

        /* ── PANEL / CARD ── */
        .panel {
            background: var(--card);
            border: 1px solid var(--line-dim);
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow);
        }

        /* ── STATS ── */
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 24px; }
        .stat-card {
            background: var(--card2);
            border: 1px solid var(--line-dim);
            border-radius: 14px;
            padding: 18px;
            box-shadow: var(--shadow);
            transition: border-color .18s;
        }
        .stat-card:hover { border-color: rgba(255,215,0,0.22); }
        .stat-label { color: var(--muted); font-size: 11px; margin-bottom: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-value { font-family: 'Orbitron', sans-serif; font-size: 26px; font-weight: 700; color: var(--gold); }

        /* ── TABLE ── */
        .table-wrap { overflow-x: auto; }
        .responsive-table { overflow-x: visible; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px 10px; border-bottom: 1px solid var(--line-dim); vertical-align: top; }
        th { color: var(--muted); font-size: 11px; font-weight: 800; background: rgba(255,255,255,0.02); text-transform: uppercase; letter-spacing: 0.5px; }

        /* ── FORM INPUTS ── */
        input, select, textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--line-dim);
            border-radius: 10px;
            background: var(--bg);
            color: var(--text);
            font-family: 'Exo 2', sans-serif;
            font-size: 14px;
            transition: border-color .18s, box-shadow .18s;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: rgba(255,215,0,0.4);
            box-shadow: 0 0 0 3px rgba(255,215,0,0.08);
        }
        select option { background: var(--card2); color: var(--text); }
        textarea { min-height: 96px; resize: vertical; }
        label { display: block; font-size: 12px; color: var(--muted); margin-bottom: 6px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; }

        /* ── BADGE ── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 999px;
            background: var(--gold-soft);
            color: var(--gold);
            font-size: 11px;
            font-weight: 800;
            border: 1px solid rgba(255,215,0,0.2);
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        .badge.off { background: rgba(230,57,70,0.12); color: var(--red); border-color: rgba(230,57,70,0.25); }
        .badge-green { background: rgba(6,214,160,0.1); color: var(--green); border-color: rgba(6,214,160,0.25); }
        .badge-red { background: rgba(230,57,70,0.12); color: var(--red); border-color: rgba(230,57,70,0.25); }
        .badge-blue { background: rgba(26,107,255,0.12); color: #66AAFF; border-color: rgba(26,107,255,0.25); }

        /* ── BUTTONS ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 0;
            background: linear-gradient(135deg, var(--gold), #FF9500);
            color: #000;
            padding: 10px 16px;
            border-radius: 10px;
            cursor: pointer;
            font-family: 'Rajdhani', sans-serif;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            box-shadow: 0 6px 20px rgba(255,215,0,0.25);
            transition: all .18s;
        }
        .btn:hover { box-shadow: 0 8px 28px rgba(255,215,0,0.4); transform: translateY(-1px); }
        .btn-secondary {
            background: transparent;
            color: var(--text);
            box-shadow: none;
            border: 1px solid var(--line-dim);
        }
        .btn-secondary:hover { border-color: rgba(255,215,0,0.3); color: var(--gold); background: var(--gold-soft); transform: none; box-shadow: none; }
        .btn-danger {
            background: linear-gradient(135deg, var(--red), #c0202e);
            color: #fff;
            box-shadow: 0 6px 20px rgba(230,57,70,0.25);
        }
        .btn-danger:hover { box-shadow: 0 8px 28px rgba(230,57,70,0.4); }

        /* ── FLASH / ERROR ── */
        .stack { display: grid; gap: 16px; }
        .flash { background: rgba(6,214,160,0.1); border: 1px solid rgba(6,214,160,0.3); color: var(--green); padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-weight: 600; }
        .error-list { background: rgba(230,57,70,0.1); border: 1px solid rgba(230,57,70,0.3); color: var(--red); padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-weight: 600; }

        /* ── MISC ── */
        .muted { color: var(--muted); }
        .text-link { color: var(--green); font-weight: 700; }
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; gap: 12px; flex-wrap: wrap; }

        /* ── GRID UTILS ── */
        .split-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .split-main-aside { display: grid; grid-template-columns: 1.15fr 0.85fr; gap: 16px; }
        .split-wide-narrow { display: grid; grid-template-columns: 0.95fr 1.05fr; gap: 16px; }
        .mobile-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .highlight-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }

        /* ── HIGHLIGHT CARDS ── */
        .highlight-card { padding: 20px; border-radius: 16px; border: 1px solid var(--line-dim); box-shadow: var(--shadow); background: var(--card2); }
        .live-card { border-top: 2px solid var(--gold); }
        .running-card { border-top: 2px solid var(--green); }
        .live-callout { min-width: 220px; padding: 16px; border-radius: 14px; background: rgba(255,215,0,0.05); border: 1px solid var(--line); }
        .highlight-top { display: flex; justify-content: space-between; gap: 10px; align-items: center; margin-bottom: 14px; }
        .highlight-value { font-family: 'Orbitron', sans-serif; font-size: 38px; font-weight: 900; color: var(--gold); line-height: 1; margin-bottom: 8px; }
        .highlight-label { font-size: 16px; font-weight: 800; margin-bottom: 6px; }
        .highlight-sub { color: var(--muted); line-height: 1.6; font-size: 14px; }

        /* ── LIVE/RUNNING PILLS ── */
        .live-pill, .running-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 1px;
            text-transform: uppercase;
            width: max-content;
            font-family: 'Orbitron', sans-serif;
        }
        .live-pill { background: rgba(230,57,70,0.15); color: var(--red); border: 1px solid rgba(230,57,70,0.3); animation: livePulse 1.8s infinite; }
        .running-pill { background: rgba(6,214,160,0.12); color: var(--green); border: 1px solid rgba(6,214,160,0.3); animation: livePulse 1.8s infinite; }
        .live-pill::before, .running-pill::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; display: inline-block; }
        @keyframes livePulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }

        /* ── MODAL ── */
        .modal-shell { position: fixed; inset: 0; display: none; z-index: 60; }
        .modal-shell.is-open { display: block; }
        .modal-backdrop { position: absolute; inset: 0; background: rgba(0,0,0,0.82); backdrop-filter: blur(6px); }
        .modal-card {
            position: relative; z-index: 1;
            width: min(1100px, calc(100vw - 32px));
            max-height: calc(100vh - 32px);
            overflow: auto;
            margin: 16px auto;
            background: var(--card);
            border-radius: 18px;
            padding: 22px;
            border: 1px solid var(--line);
            box-shadow: 0 0 60px rgba(255,215,0,0.08), 0 24px 80px rgba(0,0,0,0.8);
        }
        .modal-head {
            display: flex; justify-content: space-between; gap: 12px; align-items: flex-start;
            margin-bottom: 18px; padding-bottom: 14px;
            border-bottom: 1px solid var(--line-dim);
        }
        .modal-close {
            border: 1px solid var(--line-dim);
            background: var(--card2);
            color: var(--muted);
            width: 34px; height: 34px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 20px;
            line-height: 1;
            display: flex; align-items: center; justify-content: center;
            transition: all .15s;
        }
        .modal-close:hover { color: var(--gold); border-color: rgba(255,215,0,0.3); }

        /* ── RESPONSIVE ── */
        @media (max-width: 900px) {
            .admin-shell { grid-template-columns: 1fr; }
            .sidebar {
                position: fixed;
                top: 0; left: -280px;
                width: 260px; height: 100vh;
                z-index: 70;
                transition: left .24s ease;
                padding-bottom: 32px;
            }
            .admin-shell.sidebar-open .sidebar { left: 0; }
            .admin-shell.sidebar-open .sidebar-backdrop { display: block; }
            .menu-toggle { display: inline-flex; }
            .topbar, .header-row { flex-direction: column; align-items: flex-start; }
            .content { padding: 16px; }
            .split-2, .split-main-aside, .split-wide-narrow, .highlight-grid { grid-template-columns: 1fr; }
            .live-callout { min-width: 0; width: 100%; }
            .modal-card { width: calc(100vw - 20px); margin: 10px auto; padding: 16px; }
        }
        @media (max-width: 600px) {
            .content { padding: 12px; }
            .topbar { padding: 14px; }
            .panel, .stat-card { padding: 14px; border-radius: 14px; }
            .stat-value { font-size: 22px; }
            .mobile-actions .btn, .mobile-actions .btn-secondary { width: 100%; text-align: center; justify-content: center; }
            .table-wrap { margin: 0 -4px; }
        }
        @media (max-width: 700px) {
            .responsive-table table, .responsive-table thead, .responsive-table tbody,
            .responsive-table tr, .responsive-table th, .responsive-table td { display: block; width: 100%; }
            .responsive-table thead { display: none; }
            .responsive-table tbody { display: grid; gap: 10px; }
            .responsive-table tr { padding: 14px; border: 1px solid var(--line-dim); border-radius: 14px; background: var(--card2); box-shadow: var(--shadow); }
            .responsive-table td { padding: 8px 0; border-bottom: 1px dashed var(--line-dim); display: grid; grid-template-columns: minmax(110px, 42%) 1fr; gap: 10px; align-items: flex-start; }
            .responsive-table td:last-child { border-bottom: 0; padding-bottom: 0; }
            .responsive-table td::before { content: attr(data-label); font-size: 11px; font-weight: 800; color: var(--gold); text-transform: uppercase; letter-spacing: 0.5px; }
            .responsive-table td[colspan] { display: block; }
            .responsive-table td[colspan]::before { content: none; }
        }
    </style>
</head>
<body>
<?php ($errors = $errors ?? new \Illuminate\Support\ViewErrorBag()); ?>
<div class="admin-shell" id="adminPanelShell">
    <div class="sidebar-backdrop" data-sidebar-close="adminPanelShell"></div>
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">🎲</div>
            <div>
                <div class="brand-label">RoxLudo</div>
                <div class="brand-sub">Admin Panel</div>
            </div>
        </div>

        <div class="nav-section">Main</div>
        <a class="nav-link <?php echo e(request()->routeIs('admin.dashboard') ? 'active' : ''); ?>" href="<?php echo e(route('admin.dashboard')); ?>">
            <span class="ni">🏠</span> Dashboard
        </a>
        <a class="nav-link <?php echo e(request()->routeIs('admin.games.index') ? 'active' : ''); ?>" href="<?php echo e(route('admin.games.index')); ?>">
            <span class="ni">🎮</span> Games
        </a>
        <a class="nav-link sub <?php echo e(request()->routeIs('admin.games.ludo-tables.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.games.ludo-tables.index')); ?>">↳ Classic Ludo Tables</a>

        <div class="nav-section">Tournaments</div>
        <a class="nav-link <?php echo e(request()->routeIs('admin.tournaments.index') || request()->routeIs('admin.tournaments.create') ? 'active' : ''); ?>" href="<?php echo e(route('admin.tournaments.index')); ?>">
            <span class="ni">🏆</span> Tournaments
        </a>
        <a class="nav-link sub <?php echo e(request()->routeIs('admin.tournaments.matches') ? 'active' : ''); ?>" href="<?php echo e(route('admin.tournaments.matches')); ?>">↳ Match Monitor</a>

        <div class="nav-section">Users & Support</div>
        <a class="nav-link <?php echo e(request()->routeIs('admin.users.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.users.index')); ?>">
            <span class="ni">👥</span> Users
        </a>
        <a class="nav-link <?php echo e(request()->routeIs('admin.support.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.support.index')); ?>">
            <span class="ni">💬</span> Support Tickets
        </a>

        <div class="nav-section">Finance</div>
        <a class="nav-link <?php echo e(request()->routeIs('admin.wallet-transactions.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.wallet-transactions.index')); ?>">
            <span class="ni">💳</span> Wallet Transactions
        </a>
        <a class="nav-link <?php echo e(request()->routeIs('admin.deposits.index') ? 'active' : ''); ?>" href="<?php echo e(route('admin.deposits.index')); ?>">
            <span class="ni">⬇️</span> Deposits
        </a>
        <a class="nav-link sub <?php echo e(request()->routeIs('admin.deposits.bonus.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.deposits.bonus.index')); ?>">↳ Deposit Bonus</a>
        <a class="nav-link sub <?php echo e(request()->routeIs('admin.deposits.percentage.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.deposits.percentage.index')); ?>">↳ Deposit Percentage</a>
        <a class="nav-link <?php echo e(request()->routeIs('admin.withdrawals.index') ? 'active' : ''); ?>" href="<?php echo e(route('admin.withdrawals.index')); ?>">
            <span class="ni">⬆️</span> Withdrawals
        </a>
        <a class="nav-link sub <?php echo e(request()->routeIs('admin.withdrawals.redeem.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.withdrawals.redeem.index')); ?>">↳ Redeem Presets</a>

        <div class="nav-section">Gateways</div>
        <a class="nav-link <?php echo e(request()->routeIs('admin.gateways.manual.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.gateways.manual.index')); ?>">
            <span class="ni">🔌</span> Manual Gateways
        </a>
        <a class="nav-link <?php echo e(request()->routeIs('admin.gateways.agent.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.gateways.agent.index')); ?>">
            <span class="ni">🔌</span> Agent Gateways
        </a>
        <a class="nav-link sub <?php echo e(request()->routeIs('admin.gateways.agent-withdraw.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.gateways.agent-withdraw.index')); ?>">↳ Agent Withdraw</a>
        <a class="nav-link <?php echo e(request()->routeIs('admin.gateways.distributor.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.gateways.distributor.index')); ?>">
            <span class="ni">🔌</span> Distributor Gateways
        </a>
        <a class="nav-link sub <?php echo e(request()->routeIs('admin.gateways.distributor-withdraw.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.gateways.distributor-withdraw.index')); ?>">↳ Distributor Withdraw</a>

        <div class="nav-section">Network</div>
        <a class="nav-link <?php echo e(request()->routeIs('admin.agents.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.agents.index')); ?>">
            <span class="ni">🤝</span> Agents
        </a>
        <a class="nav-link sub <?php echo e(request()->routeIs('admin.agents.create') ? 'active' : ''); ?>" href="<?php echo e(route('admin.agents.create')); ?>">↳ Add Agent</a>
        <a class="nav-link <?php echo e(request()->routeIs('admin.distributors.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.distributors.index')); ?>">
            <span class="ni">📦</span> Distributors
        </a>
        <a class="nav-link sub <?php echo e(request()->routeIs('admin.distributors.create') ? 'active' : ''); ?>" href="<?php echo e(route('admin.distributors.create')); ?>">↳ Add Distributor</a>

        <div class="nav-section">Reports & Config</div>
        <a class="nav-link <?php echo e(request()->routeIs('admin.legacy-reports.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.legacy-reports.index')); ?>">
            <span class="ni">📊</span> Legacy Reports
        </a>
        <a class="nav-link sub <?php echo e(request()->routeIs('admin.legacy-reports.purchase-history') ? 'active' : ''); ?>" href="<?php echo e(route('admin.legacy-reports.purchase-history')); ?>">↳ Purchase History</a>
        <a class="nav-link sub <?php echo e(request()->routeIs('admin.legacy-reports.deposit-bonus') ? 'active' : ''); ?>" href="<?php echo e(route('admin.legacy-reports.deposit-bonus')); ?>">↳ Deposit Bonus</a>
        <a class="nav-link sub <?php echo e(request()->routeIs('admin.legacy-reports.bet-commission') ? 'active' : ''); ?>" href="<?php echo e(route('admin.legacy-reports.bet-commission')); ?>">↳ Bet Commission</a>
        <a class="nav-link sub <?php echo e(request()->routeIs('admin.legacy-reports.rebate-history') ? 'active' : ''); ?>" href="<?php echo e(route('admin.legacy-reports.rebate-history')); ?>">↳ Rebate History</a>
        <a class="nav-link sub <?php echo e(request()->routeIs('admin.legacy-reports.welcome-bonus') ? 'active' : ''); ?>" href="<?php echo e(route('admin.legacy-reports.welcome-bonus')); ?>">↳ Welcome Bonus</a>
        <a class="nav-link sub <?php echo e(request()->routeIs('admin.legacy-reports.withdrawal-logs') ? 'active' : ''); ?>" href="<?php echo e(route('admin.legacy-reports.withdrawal-logs')); ?>">↳ Withdrawal Logs</a>
        <a class="nav-link sub <?php echo e(request()->routeIs('admin.legacy-reports.redeem-list') ? 'active' : ''); ?>" href="<?php echo e(route('admin.legacy-reports.redeem-list')); ?>">↳ Redeem List</a>
        <a class="nav-link <?php echo e(request()->routeIs('admin.audit-logs.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.audit-logs.index')); ?>">
            <span class="ni">🔍</span> Audit Logs
        </a>
        <a class="nav-link <?php echo e(request()->routeIs('admin.settings.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.settings.app')); ?>">
            <span class="ni">⚙️</span> Settings
        </a>
        <a class="nav-link <?php echo e(request()->routeIs('admin.homepage-cards.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.homepage-cards.index')); ?>">
            <span class="ni">🏠</span> Homepage Cards
        </a>
    </aside>

    <main class="content">
        <div class="topbar">
            <div class="topbar-main">
                <button type="button" class="menu-toggle" data-sidebar-open="adminPanelShell">☰</button>
                <div>
                    <div class="topbar-heading"><?php echo $__env->yieldContent('heading', 'Admin'); ?></div>
                    <div class="topbar-sub"><?php echo $__env->yieldContent('subheading', 'Operational control panel'); ?></div>
                </div>
            </div>
            <form method="POST" action="<?php echo e(route('admin.logout')); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn btn-secondary">Logout</button>
            </form>
        </div>

        <?php if(session('status')): ?>
            <div class="flash"><?php echo e(session('status')); ?></div>
        <?php endif; ?>
        <?php if($errors->any()): ?>
            <div class="error-list">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><div><?php echo e($error); ?></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>

        <?php echo $__env->yieldContent('content'); ?>
    </main>
</div>

<?php echo $__env->yieldPushContent('scripts'); ?>
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
<?php /**PATH D:\Live-Code\Live-Rox-Ludo\games\backend_laravel\resources\views/admin/layouts/app.blade.php ENDPATH**/ ?>