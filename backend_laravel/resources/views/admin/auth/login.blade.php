<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: linear-gradient(135deg, #0f172a, #1d4ed8); font-family: "Segoe UI", sans-serif; }
        .card { width: min(420px, calc(100vw - 32px)); background: #fff; border-radius: 18px; padding: 28px; box-shadow: 0 20px 60px rgba(0,0,0,0.25); }
        h1 { margin: 0 0 8px; font-size: 28px; }
        p { margin: 0 0 20px; color: #475467; }
        label { display: block; margin-bottom: 6px; font-size: 14px; font-weight: 600; }
        input { width: 100%; padding: 12px 14px; border: 1px solid #d0d5dd; border-radius: 10px; margin-bottom: 16px; }
        button { width: 100%; border: 0; background: #0f766e; color: #fff; padding: 12px 14px; border-radius: 10px; font-weight: 700; cursor: pointer; }
        .error { background: #fee4e2; color: #b42318; border: 1px solid #fecdca; padding: 10px 12px; border-radius: 10px; margin-bottom: 16px; }
    </style>
</head>
<body>
    @php($errors = $errors ?? new \Illuminate\Support\ViewErrorBag())
    <form class="card" method="POST" action="{{ route('admin.login.submit') }}">
        @csrf
        <h1>Admin Login</h1>
        <p>Use the seeded super admin account to enter the panel.</p>

        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required>

        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>

        <button type="submit">Sign In</button>
    </form>
</body>
</html>
