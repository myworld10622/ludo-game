<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login — RoxLudo</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;900&family=Exo+2:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #0A0A14;
            font-family: 'Exo 2', sans-serif;
            color: #F0F0FF;
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,215,0,0.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,215,0,0.025) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
        }
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
        }
        .orb-1 { width: 400px; height: 400px; background: rgba(255,215,0,0.06); top: -100px; right: -100px; }
        .orb-2 { width: 300px; height: 300px; background: rgba(26,107,255,0.07); bottom: -80px; left: -80px; }

        .card {
            position: relative;
            z-index: 1;
            width: min(420px, calc(100vw - 32px));
            background: #12121F;
            border: 1px solid rgba(255,215,0,0.14);
            border-radius: 20px;
            padding: 36px 32px;
            box-shadow: 0 0 60px rgba(255,215,0,0.06), 0 24px 80px rgba(0,0,0,0.7);
        }
        .card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, #FFD700, #FF9500, transparent);
            border-radius: 20px 20px 0 0;
        }

        .login-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
        }
        .brand-icon {
            width: 44px; height: 44px;
            background: linear-gradient(135deg, #FFD700, #E63946);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
            filter: drop-shadow(0 0 12px rgba(255,215,0,0.4));
        }
        .brand-text-wrap {}
        .brand-name {
            font-family: 'Orbitron', sans-serif;
            font-size: 18px;
            font-weight: 700;
            background: linear-gradient(135deg, #FFD700, #FF6B6B);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: 1px;
        }
        .brand-role {
            font-size: 11px;
            color: rgba(136,136,170,0.7);
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-top: 1px;
        }

        h1 {
            font-family: 'Exo 2', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: #F0F0FF;
            margin-bottom: 6px;
        }
        .sub {
            font-size: 13px;
            color: #8888AA;
            margin-bottom: 28px;
            line-height: 1.5;
        }

        .error {
            background: rgba(230,57,70,0.1);
            color: #E63946;
            border: 1px solid rgba(230,57,70,0.3);
            padding: 10px 14px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-size: 11px;
            font-weight: 800;
            color: #8888AA;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 10px;
            margin-bottom: 18px;
            background: #0A0A14;
            color: #F0F0FF;
            font-family: 'Exo 2', sans-serif;
            font-size: 14px;
            transition: border-color .18s, box-shadow .18s;
        }
        input:focus {
            outline: none;
            border-color: rgba(255,215,0,0.4);
            box-shadow: 0 0 0 3px rgba(255,215,0,0.08);
        }

        button[type="submit"] {
            width: 100%;
            border: 0;
            background: linear-gradient(135deg, #FFD700, #FF9500);
            color: #000;
            padding: 13px 14px;
            border-radius: 12px;
            font-family: 'Exo 2', sans-serif;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            letter-spacing: 1px;
            text-transform: uppercase;
            box-shadow: 0 8px 28px rgba(255,215,0,0.28);
            transition: all .2s;
            margin-top: 4px;
        }
        button[type="submit"]:hover {
            box-shadow: 0 12px 36px rgba(255,215,0,0.45);
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <?php ($errors = $errors ?? new \Illuminate\Support\ViewErrorBag()); ?>
    <form class="card" method="POST" action="<?php echo e(route('admin.login.submit')); ?>">
        <?php echo csrf_field(); ?>
        <div class="login-brand">
            <div class="brand-icon">🎲</div>
            <div class="brand-text-wrap">
                <div class="brand-name">RoxLudo</div>
                <div class="brand-role">Admin Panel</div>
            </div>
        </div>

        <h1>Sign In</h1>
        <p class="sub">Enter your admin credentials to access the control panel.</p>

        <?php if($errors->any()): ?>
            <div class="error">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><div><?php echo e($error); ?></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>

        <label for="email">Email Address</label>
        <input id="email" name="email" type="email" value="<?php echo e(old('email')); ?>" required placeholder="admin@roxludo.com">

        <label for="password">Password</label>
        <input id="password" name="password" type="password" required placeholder="••••••••">

        <button type="submit">Sign In →</button>
    </form>
</body>
</html>
<?php /**PATH D:\Live-Code\Live-Rox-Ludo\games\backend_laravel\resources\views/admin/auth/login.blade.php ENDPATH**/ ?>