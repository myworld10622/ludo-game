<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Login</title>
    <style>
        :root {
            --bg-a: #081225;
            --bg-b: #13264a;
            --card: rgba(255, 255, 255, 0.96);
            --text: #132238;
            --muted: #5d6b7b;
            --line: #d6dde6;
            --brand: #0f766e;
            --error-bg: #fee4e2;
            --error-line: #fecdca;
            --error-text: #b42318;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background:
                radial-gradient(circle at top, rgba(255, 184, 77, 0.18), transparent 34%),
                linear-gradient(135deg, var(--bg-a), var(--bg-b));
            font-family: "Segoe UI", sans-serif;
            color: var(--text);
        }
        .card {
            width: min(460px, calc(100vw - 32px));
            background: var(--card);
            border-radius: 22px;
            padding: 30px;
            box-shadow: 0 24px 70px rgba(0, 0, 0, 0.28);
        }
        h1 { margin: 0 0 8px; font-size: 30px; }
        p { margin: 0 0 20px; color: var(--muted); line-height: 1.45; }
        label { display: block; margin-bottom: 6px; font-size: 14px; font-weight: 700; }
        input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid var(--line);
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 15px;
        }
        button {
            width: 100%;
            border: 0;
            background: var(--brand);
            color: #fff;
            padding: 13px 14px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
        }
        .error {
            background: var(--error-bg);
            color: var(--error-text);
            border: 1px solid var(--error-line);
            padding: 10px 12px;
            border-radius: 12px;
            margin-bottom: 16px;
        }
        .hint {
            margin-top: 14px;
            font-size: 13px;
            color: var(--muted);
        }
    </style>
</head>
<body>
    @php($errors = $errors ?? new \Illuminate\Support\ViewErrorBag())
    <form class="card" method="POST" action="{{ route('user.login.submit') }}">
        @csrf
        <h1>User Panel Login</h1>
        <p>Sign in with your User ID, username, email address, or mobile number to open your tournament panel.</p>

        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <label for="identity">Login ID</label>
        <input
            id="identity"
            name="identity"
            type="text"
            value="{{ old('identity') }}"
            placeholder="User ID / Username / Email / Mobile"
            required
        >

        <label for="password">Password</label>
        <input id="password" name="password" type="password" placeholder="Enter your password" required>

        <button type="submit">Sign In</button>
        <div class="hint">Example: `65261474`, `username`, `name@example.com`, or mobile number.</div>
    </form>
</body>
</html>
