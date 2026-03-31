<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $__env->yieldContent('title', 'User Panel'); ?></title>
    <style>
        :root {
            --bg:#f6efe5;
            --bg-accent:#ffe7c8;
            --panel:#fffdf9;
            --panel-strong:#ffffff;
            --line:#ead9c6;
            --text:#1f2430;
            --muted:#6f6a62;
            --brand:#d96c2f;
            --brand-dark:#9f3f15;
            --brand-soft:#ffe0c9;
            --accent:#17806d;
            --accent-soft:#d9f6ef;
            --shadow:0 20px 60px rgba(110,63,20,0.12);
        }
        * { box-sizing:border-box; }
        body {
            margin:0;
            font-family:"Segoe UI",sans-serif;
            background:
                radial-gradient(circle at top left, rgba(255,231,200,0.95), transparent 32%),
                radial-gradient(circle at bottom right, rgba(217,108,47,0.10), transparent 26%),
                linear-gradient(180deg, #fff8ef 0%, var(--bg) 100%);
            color:var(--text);
            overflow-x:hidden;
        }
        a { color:inherit; text-decoration:none; }
        .shell { display:grid; grid-template-columns:250px 1fr; min-height:100vh; position:relative; }
        .sidebar {
            background:
                linear-gradient(180deg, rgba(73,33,13,0.96) 0%, rgba(35,20,14,0.98) 100%);
            color:#f9e8d8;
            padding:24px 18px;
            border-right:1px solid rgba(255,255,255,0.08);
            box-shadow:inset -1px 0 0 rgba(255,255,255,0.05);
        }
        .brand {
            font-size:20px;
            font-weight:800;
            margin-bottom:24px;
            letter-spacing:0.02em;
            color:#fff5eb;
        }
        .nav-link {
            display:block;
            padding:11px 13px;
            border-radius:12px;
            margin-bottom:8px;
            color:#f3d8c0;
            transition:all .18s ease;
        }
        .nav-link.active,.nav-link:hover {
            background:linear-gradient(90deg, rgba(217,108,47,0.24), rgba(255,255,255,0.08));
            color:#fff;
            transform:translateX(2px);
        }
        .content { padding:24px; }
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
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:24px;
            gap:16px;
            padding:18px 20px;
            border:1px solid rgba(234,217,198,0.9);
            border-radius:18px;
            background:linear-gradient(135deg, rgba(255,253,249,0.92), rgba(255,244,231,0.92));
            backdrop-filter:blur(8px);
            box-shadow:var(--shadow);
        }
        .panel {
            background:linear-gradient(180deg, var(--panel-strong) 0%, var(--panel) 100%);
            border:1px solid var(--line);
            border-radius:18px;
            padding:18px;
            box-shadow:var(--shadow);
        }
        .stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:16px; margin-bottom:24px; }
        .stat-card {
            background:linear-gradient(180deg, #fff 0%, #fff7ee 100%);
            border:1px solid var(--line);
            border-radius:18px;
            padding:18px;
            box-shadow:var(--shadow);
        }
        .stat-label { color:var(--muted); font-size:13px; margin-bottom:8px; }
        .stat-value {
            font-size:28px;
            font-weight:800;
            color:var(--brand-dark);
        }
        .btn {
            border:0;
            background:linear-gradient(135deg, var(--brand) 0%, #ef8a43 100%);
            color:#fff;
            padding:10px 14px;
            border-radius:12px;
            cursor:pointer;
            box-shadow:0 10px 25px rgba(217,108,47,0.24);
            font-weight:700;
        }
        .btn-secondary {
            background:linear-gradient(135deg, #fff5ea 0%, #f8e3cf 100%);
            color:var(--brand-dark);
            box-shadow:none;
            border:1px solid var(--line);
        }
        .flash { background:#ecfdf3; border:1px solid #abefc6; color:#067647; padding:12px 14px; border-radius:10px; margin-bottom:18px; }
        .error-list { background:#fee4e2; border:1px solid #fecdca; color:#b42318; padding:12px 14px; border-radius:10px; margin-bottom:18px; }
        .muted { color:var(--muted); }
        .badge {
            display:inline-block;
            padding:5px 9px;
            border-radius:999px;
            background:linear-gradient(135deg, var(--brand-soft), #fff0e2);
            color:var(--brand-dark);
            font-size:12px;
            font-weight:800;
            border:1px solid rgba(217,108,47,0.14);
        }
        table { width:100%; border-collapse:collapse; }
        th,td { text-align:left; padding:12px 10px; border-bottom:1px solid rgba(234,217,198,0.8); vertical-align:top; }
        th { color:var(--muted); font-size:13px; font-weight:700; background:rgba(255,246,236,0.7); }
        .table-wrap { overflow-x:auto; }
        .responsive-table { overflow-x:visible; }
        input, select, textarea {
            width:100%;
            padding:10px;
            border:1px solid var(--line);
            border-radius:12px;
            background:#fffdfa;
            color:var(--text);
        }
        input:focus, select:focus, textarea:focus {
            outline:none;
            border-color:#ef8a43;
            box-shadow:0 0 0 4px rgba(239,138,67,0.12);
        }
        textarea { min-height:96px; resize:vertical; }
        label { display:block; font-size:13px; color:var(--muted); margin-bottom:6px; }
        .page-hero { display:flex; justify-content:space-between; gap:18px; align-items:flex-start; }
        .eyebrow { font-size:12px; letter-spacing:0.12em; text-transform:uppercase; color:var(--brand); font-weight:700; }
        .hero-stats { display:grid; gap:10px; min-width:220px; }
        .hero-chip { border:1px solid var(--line); background:linear-gradient(180deg, #fff6ee 0%, #fff 100%); border-radius:16px; padding:14px; display:grid; gap:4px; }
        .hero-chip strong { font-size:24px; }
        .hero-chip span { color:var(--muted); font-size:12px; }
        .live-callout { min-width:220px; padding:16px; border-radius:18px; background:rgba(255,255,255,0.14); border:1px solid rgba(255,255,255,0.18); backdrop-filter:blur(6px); }
        .form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px; }
        .stack-compact { display:grid; gap:12px; }
        .prize-grid { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:10px; }
        .cards-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:16px; }
        .split-2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .split-main-aside { display:grid; grid-template-columns:1.2fr 0.8fr; gap:16px; }
        .split-wide-narrow { display:grid; grid-template-columns:0.95fr 1.05fr; gap:16px; }
        .mobile-actions { display:flex; gap:10px; flex-wrap:wrap; }
        .highlight-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
        .highlight-card { padding:20px; border-radius:20px; border:1px solid var(--line); box-shadow:var(--shadow); }
        .live-card { background:linear-gradient(135deg, #fff1e2 0%, #ffe3c2 52%, #fffdf7 100%); }
        .running-card { background:linear-gradient(135deg, #e7fff8 0%, #d8f6ef 52%, #fffdf7 100%); }
        .highlight-top { display:flex; justify-content:space-between; gap:10px; align-items:center; margin-bottom:14px; }
        .highlight-value { font-size:42px; font-weight:900; color:var(--brand-dark); line-height:1; margin-bottom:8px; }
        .highlight-label { font-size:16px; font-weight:800; margin-bottom:6px; }
        .highlight-sub { color:var(--muted); line-height:1.6; }
        .tournament-card { display:grid; gap:16px; }
        .card-head { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; }
        .card-title { font-size:20px; font-weight:700; }
        .metrics-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; }
        .metrics-grid div { border:1px solid var(--line); border-radius:14px; background:linear-gradient(180deg, #fffaf4 0%, #fff 100%); padding:12px; display:grid; gap:4px; }
        .metrics-grid span, .details-grid span { color:var(--muted); font-size:12px; }
        .metrics-grid strong, .details-grid strong { font-size:16px; }
        .card-actions { display:flex; gap:8px; flex-wrap:wrap; }
        .fake-form { display:grid; gap:8px; padding:12px; border:1px dashed #e7b68a; border-radius:12px; background:#fff8f1; }
        .text-link { color:var(--accent); font-weight:800; }
        .tabs-bar { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; }
        .tab-chip { padding:10px 12px; border:1px solid var(--line); background:linear-gradient(180deg, #fffaf4 0%, #fff 100%); border-radius:999px; font-weight:700; color:var(--brand-dark); }
        .report-section { margin-bottom:18px; }
        .section-title { font-size:18px; font-weight:700; margin-bottom:14px; }
        .details-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:12px; }
        .details-grid div { border:1px solid var(--line); border-radius:14px; padding:14px; background:linear-gradient(180deg, #fff8f1 0%, #fff 100%); display:grid; gap:5px; }
        .note-box { margin-top:14px; border:1px solid var(--line); background:linear-gradient(180deg, #fff8f1 0%, #fff 100%); border-radius:14px; padding:14px; }
        .note-title { font-weight:700; margin-bottom:6px; }
        .round-stack { display:grid; gap:14px; }
        .round-card { border:1px solid var(--line); border-radius:16px; padding:14px; background:linear-gradient(180deg, #fffaf5 0%, #fff 100%); }
        .round-head { display:flex; justify-content:space-between; gap:10px; align-items:flex-start; margin-bottom:12px; }
        .badge-warn { background:#fff2c9; color:#92400e; border-color:#f1c96f; }
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
        .modal-shell { position:fixed; inset:0; display:none; z-index:60; }
        .modal-shell.is-open { display:block; }
        .modal-backdrop { position:absolute; inset:0; background:rgba(15,23,42,0.6); }
        .modal-card { position:relative; z-index:1; width:min(1100px, calc(100vw - 32px)); max-height:calc(100vh - 32px); overflow:auto; margin:16px auto; background:linear-gradient(180deg, #fffdfa 0%, #fff5ea 100%); border-radius:20px; padding:20px; border:1px solid var(--line); box-shadow:0 24px 80px rgba(69,36,14,0.28); }
        .modal-head { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; margin-bottom:16px; padding-bottom:12px; border-bottom:1px solid rgba(234,217,198,0.8); }
        .modal-close { border:0; background:#fff0e0; color:var(--brand-dark); width:36px; height:36px; border-radius:999px; cursor:pointer; font-size:24px; line-height:1; }
        @media (max-width:900px) {
            .shell { grid-template-columns:1fr; }
            .sidebar {
                position:fixed;
                top:0;
                left:-290px;
                width:270px;
                height:100vh;
                z-index:70;
                transition:left .24s ease;
                overflow-y:auto;
            }
            .shell.sidebar-open .sidebar { left:0; }
            .shell.sidebar-open .sidebar-backdrop { display:block; }
            .menu-toggle { display:inline-flex; align-items:center; justify-content:center; }
            .page-hero { flex-direction:column; }
            .split-2, .split-main-aside, .split-wide-narrow { grid-template-columns:1fr; }
            .highlight-grid { grid-template-columns:1fr; }
            .metrics-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .prize-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .topbar, .header-row { flex-direction:column; align-items:flex-start; }
            .content { padding:16px; }
            .hero-stats, .live-callout { min-width:0; width:100%; }
            .modal-card { width:calc(100vw - 20px); margin:10px auto; padding:16px; }
        }
        @media (max-width:600px) {
            .content { padding:12px; }
            .topbar { padding:14px; }
            .panel, .stat-card { padding:14px; border-radius:16px; }
            .stat-value { font-size:24px; }
            .mobile-actions .btn, .mobile-actions .btn-secondary { width:100%; text-align:center; }
            .card-actions { flex-direction:column; }
            .card-actions .btn, .card-actions .btn-secondary { width:100%; text-align:center; }
            .table-wrap { margin:0 -4px; }
            .metrics-grid, .prize-grid { grid-template-columns:1fr; }
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
<?php ($panelUser = auth('web')->user()); ?>
<?php ($panelPermissions = $panelUser?->panelPermissions() ?? []); ?>
<?php ($errors = $errors ?? new \Illuminate\Support\ViewErrorBag()); ?>
<div class="shell" id="userPanelShell">
    <div class="sidebar-backdrop" data-sidebar-close="userPanelShell"></div>
    <aside class="sidebar">
        <div class="brand">User Tournament Panel</div>
        <a class="nav-link <?php echo e(request()->routeIs('panel.index') ? 'active' : ''); ?>" href="<?php echo e(route('panel.index')); ?>">Dashboard</a>
        <?php if(!empty($panelPermissions['manage_tournaments'])): ?>
            <a class="nav-link <?php echo e(request()->routeIs('panel.tournaments.*') ? 'active' : ''); ?>" href="<?php echo e(route('panel.tournaments.index')); ?>">Tournaments</a>
        <?php endif; ?>
        <?php if(!empty($panelPermissions['view_match_monitor'])): ?>
            <a class="nav-link <?php echo e(request()->routeIs('panel.matches.*') ? 'active' : ''); ?>" href="<?php echo e(route('panel.matches.index')); ?>">Match Monitor</a>
        <?php endif; ?>
        <a class="nav-link <?php echo e(request()->routeIs('panel.support.*') ? 'active' : ''); ?>" href="<?php echo e(route('panel.support.index')); ?>">Support Chat</a>
    </aside>
    <main class="content">
        <div class="topbar">
            <div class="topbar-main">
                <button type="button" class="menu-toggle" data-sidebar-open="userPanelShell">☰</button>
                <div>
                    <div style="font-size:24px;font-weight:700;"><?php echo $__env->yieldContent('heading', 'User Panel'); ?></div>
                    <div class="muted"><?php echo $__env->yieldContent('subheading', 'Manage only your own tournaments and matches'); ?></div>
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
        document.getElementById('userPanelShell')?.classList.remove('sidebar-open');
    });
});
</script>
</body>
</html>
<?php /**PATH D:\Live-Code\games\backend_laravel\resources\views/user/layouts/app.blade.php ENDPATH**/ ?>