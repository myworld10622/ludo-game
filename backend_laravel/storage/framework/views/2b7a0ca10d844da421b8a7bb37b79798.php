<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $__env->yieldContent('title', 'User Panel'); ?> — RoxLudo</title>
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Exo+2:wght@300;400;600;800;900&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #0A0A14;
            --card: #12121F;
            --card2: #1A1A2E;
            --panel: #12121F;
            --panel-strong: #1A1A2E;
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
            --brand-dark: #CC9900;
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
        .shell {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
            position: relative;
            z-index: 1;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            background: linear-gradient(180deg, #0D0D1A 0%, #090910 100%);
            color: #D0D0EE;
            padding: 24px 16px;
            border-right: 1px solid var(--line-dim);
            box-shadow: inset -1px 0 0 rgba(255,215,0,0.05);
            position: relative;
        }
        .sidebar::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--gold), #FF9500, transparent);
        }

        .brand {
            font-family: 'Orbitron', sans-serif;
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 28px;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .brand-icon {
            width: 34px; height: 34px;
            background: linear-gradient(135deg, var(--gold), var(--red));
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
            filter: drop-shadow(0 0 8px rgba(255,215,0,0.35));
        }
        .brand-text {
            background: linear-gradient(135deg, var(--gold), #FF6B6B);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-section-label {
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: rgba(136,136,170,0.6);
            padding: 0 10px;
            margin: 18px 0 6px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            margin-bottom: 4px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 600;
            transition: all .18s ease;
            border: 1px solid transparent;
            letter-spacing: 0.2px;
        }
        .nav-link .nav-icon { font-size: 15px; flex-shrink: 0; }
        .nav-link.active, .nav-link:hover {
            background: var(--gold-soft);
            color: var(--gold);
            border-color: rgba(255,215,0,0.18);
            box-shadow: 0 0 12px rgba(255,215,0,0.06);
        }
        .nav-link.guide-link {
            background: rgba(6,214,160,0.07);
            color: var(--green);
            border-color: rgba(6,214,160,0.18);
            margin-top: 8px;
        }
        .nav-link.guide-link:hover {
            background: rgba(6,214,160,0.14);
            color: var(--green);
            border-color: rgba(6,214,160,0.35);
        }

        /* ── CONTENT ── */
        .content { padding: 24px; overflow-x: hidden; }

        /* ── TOPBAR ── */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            gap: 16px;
            padding: 16px 20px;
            border: 1px solid var(--line-dim);
            border-radius: 16px;
            background: var(--card);
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }
        .topbar::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,215,0,0.3), transparent);
        }
        .topbar-main { display: flex; align-items: center; gap: 12px; }
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
        .topbar-heading {
            font-family: 'Orbitron', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: var(--text);
            letter-spacing: 0.5px;
        }
        .topbar-sub { font-size: 12px; color: var(--muted); margin-top: 2px; font-weight: 400; }

        /* ── SIDEBAR BACKDROP ── */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.7);
            z-index: 69;
            backdrop-filter: blur(4px);
        }

        /* ── PANEL / CARD ── */
        .panel {
            background: var(--card);
            border: 1px solid var(--line-dim);
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow);
            position: relative;
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
        .stat-label { color: var(--muted); font-size: 12px; margin-bottom: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-value { font-family: 'Orbitron', sans-serif; font-size: 26px; font-weight: 700; color: var(--gold); }

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
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.5px;
            box-shadow: 0 6px 20px rgba(255,215,0,0.25);
            transition: all .18s;
            text-transform: uppercase;
        }
        .btn:hover { box-shadow: 0 8px 28px rgba(255,215,0,0.4); transform: translateY(-1px); }
        .btn-secondary {
            background: transparent;
            color: var(--text);
            box-shadow: none;
            border: 1px solid var(--line-dim);
        }
        .btn-secondary:hover { border-color: rgba(255,215,0,0.3); color: var(--gold); background: var(--gold-soft); transform: none; box-shadow: none; }

        /* ── FLASH / ERROR ── */
        .flash { background: rgba(6,214,160,0.1); border: 1px solid rgba(6,214,160,0.3); color: var(--green); padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-weight: 600; }
        .error-list { background: rgba(230,57,70,0.1); border: 1px solid rgba(230,57,70,0.3); color: var(--red); padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-weight: 600; }

        /* ── MUTED ── */
        .muted { color: var(--muted); }

        /* ── BADGE ── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
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
        .badge-warn { background: rgba(230,57,70,0.12); color: var(--red); border-color: rgba(230,57,70,0.25); }
        .badge-green { background: rgba(6,214,160,0.1); color: var(--green); border-color: rgba(6,214,160,0.25); }

        /* ── TABLE ── */
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px 10px; border-bottom: 1px solid var(--line-dim); vertical-align: top; }
        th { color: var(--muted); font-size: 11px; font-weight: 800; background: rgba(255,255,255,0.02); text-transform: uppercase; letter-spacing: 0.5px; }
        .table-wrap { overflow-x: auto; }
        .responsive-table { overflow-x: visible; }

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
        select option { background: var(--card2); }
        textarea { min-height: 96px; resize: vertical; }
        label { display: block; font-size: 12px; color: var(--muted); margin-bottom: 6px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; }

        /* ── PAGE HERO ── */
        .page-hero { display: flex; justify-content: space-between; gap: 18px; align-items: flex-start; }
        .eyebrow { font-size: 10px; letter-spacing: 3px; text-transform: uppercase; color: var(--gold); font-weight: 700; font-family: 'Orbitron', sans-serif; }
        .hero-stats { display: grid; gap: 10px; min-width: 220px; }
        .hero-chip {
            border: 1px solid var(--line-dim);
            background: var(--card2);
            border-radius: 12px;
            padding: 14px;
            display: grid;
            gap: 4px;
        }
        .hero-chip strong { font-family: 'Orbitron', sans-serif; font-size: 22px; color: var(--gold); }
        .hero-chip span { color: var(--muted); font-size: 12px; }

        /* ── GRIDS ── */
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; }
        .stack-compact { display: grid; gap: 12px; }
        .prize-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; }
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px; }
        .split-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .split-main-aside { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 16px; }
        .split-wide-narrow { display: grid; grid-template-columns: 0.95fr 1.05fr; gap: 16px; }
        .mobile-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .highlight-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; }
        .details-grid div { border: 1px solid var(--line-dim); border-radius: 12px; padding: 14px; background: var(--card2); display: grid; gap: 5px; }
        .details-grid span { color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; }
        .details-grid strong { font-size: 15px; }

        /* ── HIGHLIGHT CARDS ── */
        .highlight-card { padding: 20px; border-radius: 16px; border: 1px solid var(--line-dim); box-shadow: var(--shadow); background: var(--card2); }
        .live-card { border-top: 2px solid var(--gold); }
        .running-card { border-top: 2px solid var(--green); }
        .highlight-top { display: flex; justify-content: space-between; gap: 10px; align-items: center; margin-bottom: 14px; }
        .highlight-value { font-family: 'Orbitron', sans-serif; font-size: 38px; font-weight: 900; color: var(--gold); line-height: 1; margin-bottom: 8px; }
        .highlight-label { font-size: 16px; font-weight: 800; margin-bottom: 6px; }
        .highlight-sub { color: var(--muted); line-height: 1.6; font-size: 14px; }
        .live-callout { min-width: 220px; padding: 16px; border-radius: 14px; background: rgba(255,215,0,0.05); border: 1px solid var(--line); }

        /* ── TOURNAMENT CARD ── */
        .tournament-card { display: grid; gap: 16px; }
        .card-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
        .card-title { font-family: 'Exo 2', sans-serif; font-size: 18px; font-weight: 800; color: var(--text); }
        .metrics-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
        .metrics-grid div { border: 1px solid var(--line-dim); border-radius: 12px; background: var(--card2); padding: 12px; display: grid; gap: 4px; }
        .metrics-grid span { color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; }
        .metrics-grid strong { font-size: 15px; color: var(--text); }
        .card-actions { display: flex; gap: 8px; flex-wrap: wrap; }

        /* ── LIVE / RUNNING PILLS ── */
        .live-pill, .running-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
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
        @keyframes livePulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }

        /* ── MODAL ── */
        .modal-shell { position: fixed; inset: 0; display: none; z-index: 60; }
        .modal-shell.is-open { display: block; }
        .modal-backdrop { position: absolute; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(6px); }
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

        /* ── MISC ── */
        .fake-form { display: grid; gap: 8px; padding: 12px; border: 1px dashed rgba(255,215,0,0.2); border-radius: 10px; background: rgba(255,215,0,0.03); }
        .text-link { color: var(--green); font-weight: 700; }
        .tabs-bar { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 18px; }
        .tab-chip { padding: 8px 14px; border: 1px solid var(--line-dim); background: var(--card2); border-radius: 999px; font-weight: 700; color: var(--muted); font-size: 13px; cursor: pointer; transition: all .15s; }
        .tab-chip:hover, .tab-chip.active { color: var(--gold); border-color: rgba(255,215,0,0.3); background: var(--gold-soft); }
        .report-section { margin-bottom: 18px; }
        .section-title { font-family: 'Exo 2', sans-serif; font-size: 16px; font-weight: 800; margin-bottom: 14px; color: var(--text); }
        .note-box { margin-top: 14px; border: 1px solid var(--line-dim); background: var(--card2); border-radius: 12px; padding: 14px; }
        .note-title { font-weight: 700; margin-bottom: 6px; color: var(--gold); }
        .round-stack { display: grid; gap: 14px; }
        .round-card { border: 1px solid var(--line-dim); border-radius: 14px; padding: 14px; background: var(--card2); }
        .round-head { display: flex; justify-content: space-between; gap: 10px; align-items: flex-start; margin-bottom: 12px; }

        /* ── RESPONSIVE ── */
        @media (max-width: 900px) {
            .shell { grid-template-columns: 1fr; }
            .sidebar {
                position: fixed;
                top: 0; left: -280px;
                width: 260px; height: 100vh;
                z-index: 70;
                transition: left .24s ease;
                overflow-y: auto;
            }
            .shell.sidebar-open .sidebar { left: 0; }
            .shell.sidebar-open .sidebar-backdrop { display: block; }
            .menu-toggle { display: inline-flex; }
            .page-hero { flex-direction: column; }
            .split-2, .split-main-aside, .split-wide-narrow { grid-template-columns: 1fr; }
            .highlight-grid { grid-template-columns: 1fr; }
            .metrics-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .prize-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .topbar { flex-direction: column; align-items: flex-start; }
            .content { padding: 16px; }
            .hero-stats, .live-callout { min-width: 0; width: 100%; }
            .modal-card { width: calc(100vw - 20px); margin: 10px auto; padding: 16px; }
        }
        @media (max-width: 600px) {
            .content { padding: 12px; }
            .topbar { padding: 14px; }
            .panel, .stat-card { padding: 14px; border-radius: 14px; }
            .stat-value { font-size: 22px; }
            .mobile-actions .btn, .mobile-actions .btn-secondary { width: 100%; text-align: center; justify-content: center; }
            .card-actions { flex-direction: column; }
            .card-actions .btn, .card-actions .btn-secondary { width: 100%; text-align: center; justify-content: center; }
            .table-wrap { margin: 0 -4px; }
            .metrics-grid, .prize-grid { grid-template-columns: 1fr; }
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
<?php ($panelUser = auth('web')->user()); ?>
<?php ($panelPermissions = $panelUser?->panelPermissions() ?? []); ?>
<?php ($errors = $errors ?? new \Illuminate\Support\ViewErrorBag()); ?>
<div class="shell" id="userPanelShell">
    <div class="sidebar-backdrop" data-sidebar-close="userPanelShell"></div>
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">🎲</div>
            <span class="brand-text">RoxLudo</span>
        </div>
        <div class="sidebar-section-label">Navigation</div>
        <a class="nav-link <?php echo e(request()->routeIs('panel.index') ? 'active' : ''); ?>" href="<?php echo e(route('panel.index')); ?>">
            <span class="nav-icon">🏠</span> Dashboard
        </a>
        <?php if(!empty($panelPermissions['manage_tournaments'])): ?>
            <a class="nav-link <?php echo e(request()->routeIs('panel.tournaments.*') ? 'active' : ''); ?>" href="<?php echo e(route('panel.tournaments.index')); ?>">
                <span class="nav-icon">🏆</span> Tournaments
            </a>
        <?php endif; ?>
        <?php if(!empty($panelPermissions['view_match_monitor'])): ?>
            <a class="nav-link <?php echo e(request()->routeIs('panel.matches.*') ? 'active' : ''); ?>" href="<?php echo e(route('panel.matches.index')); ?>">
                <span class="nav-icon">⚡</span> Match Monitor
            </a>
        <?php endif; ?>
        <a class="nav-link <?php echo e(request()->routeIs('panel.support.*') ? 'active' : ''); ?>" href="<?php echo e(route('panel.support.index')); ?>">
            <span class="nav-icon">💬</span> Support Chat
        </a>
        <div class="sidebar-section-label">Resources</div>
        <a class="nav-link guide-link" href="<?php echo e(route('tournament.guide')); ?>" target="_blank">
            <span class="nav-icon">📖</span> Tournament Guide
        </a>
    </aside>

    <main class="content">
        <div class="topbar">
            <div class="topbar-main">
                <button type="button" class="menu-toggle" data-sidebar-open="userPanelShell">☰</button>
                <div>
                    <div class="topbar-heading"><?php echo $__env->yieldContent('heading', 'User Panel'); ?></div>
                    <div class="topbar-sub"><?php echo $__env->yieldContent('subheading', 'Manage your tournaments and matches'); ?></div>
                </div>
            </div>
            <form method="POST" action="<?php echo e(route('panel.logout')); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn btn-secondary">Logout</button>
            </form>
        </div>
        <?php if(session('status')): ?><div class="flash"><?php echo e(session('status')); ?></div><?php endif; ?>
        <?php if(session('error')): ?><div class="error-list"><?php echo e(session('error')); ?></div><?php endif; ?>
        <?php if($errors->any()): ?>
            <div class="error-list"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><div><?php echo e($error); ?></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div>
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
        if (!link.classList.contains('guide-link')) {
            document.getElementById('userPanelShell')?.classList.remove('sidebar-open');
        }
    });
});
</script>
</body>
</html>
<?php /**PATH D:\Live-Code\Live-Rox-Ludo\games\backend_laravel\resources\views/user/layouts/app.blade.php ENDPATH**/ ?>