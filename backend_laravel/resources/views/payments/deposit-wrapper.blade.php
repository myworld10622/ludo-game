<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Secure Deposit</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #180603;
            --panel: #2a0a05;
            --accent: #f5b335;
            --text: #fff4dc;
            --muted: rgba(255, 244, 220, 0.72);
            --line: rgba(245, 179, 53, 0.28);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            background:
                radial-gradient(circle at top, rgba(245, 179, 53, 0.16), transparent 38%),
                linear-gradient(180deg, #2b0905 0%, #120302 100%);
            color: var(--text);
            min-height: 100vh;
        }
        .shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .topbar {
            padding: 14px 18px;
            border-bottom: 1px solid var(--line);
            background: rgba(24, 6, 3, 0.92);
            backdrop-filter: blur(10px);
        }
        .title {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .sub {
            margin-top: 4px;
            color: var(--muted);
            font-size: 13px;
        }
        .body {
            flex: 1;
            padding: 16px;
        }
        .frame-wrap {
            height: calc(100vh - 104px);
            min-height: 520px;
            border: 1px solid var(--line);
            border-radius: 18px;
            overflow: hidden;
            background: var(--panel);
            box-shadow: 0 18px 48px rgba(0, 0, 0, 0.28);
        }
        iframe {
            width: 100%;
            height: 100%;
            border: 0;
            background: #fff;
        }
        .empty {
            display: grid;
            place-items: center;
            height: 100%;
            padding: 28px;
            text-align: center;
        }
        .empty-card {
            max-width: 460px;
            padding: 28px;
            border-radius: 16px;
            background: rgba(0, 0, 0, 0.18);
            border: 1px solid var(--line);
        }
        .empty-card h1 {
            margin: 0 0 10px;
            font-size: 22px;
        }
        .empty-card p {
            margin: 0;
            line-height: 1.6;
            color: var(--muted);
        }
        .actions {
            margin-top: 18px;
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 170px;
            padding: 12px 18px;
            border-radius: 999px;
            border: 1px solid rgba(245, 179, 53, 0.7);
            background: linear-gradient(180deg, #f5c04a 0%, #c47d11 100%);
            color: #2a1200;
            font-weight: 700;
            text-decoration: none;
        }
        .btn-secondary {
            background: transparent;
            color: var(--text);
        }
        @media (max-width: 768px) {
            .body { padding: 10px; }
            .frame-wrap {
                min-height: 440px;
                height: calc(100vh - 90px);
                border-radius: 12px;
            }
        }
    </style>
</head>
<body>
<div class="shell">
    <div class="topbar">
        <div class="title">Secure Deposit</div>
        <div class="sub">Transaction: {{ $transaction->trx }}</div>
    </div>

    <div class="body">
        <div class="frame-wrap">
            @if ($iframeAllowed)
                <iframe
                    src="{{ $paymentUrl }}"
                    title="Secure Deposit"
                    allow="payment *; clipboard-read *; clipboard-write *"
                    referrerpolicy="no-referrer"
                ></iframe>
            @else
                <div class="empty">
                    <div class="empty-card">
                        <h1>Payment link unavailable</h1>
                        <p>The secure payment session is not ready yet or has expired. Please go back to the app and start the deposit again.</p>
                    </div>
                </div>
            @endif
        </div>

        @if ($iframeAllowed)
            <div class="actions">
                <a class="btn" href="{{ $paymentUrl }}" target="_self" rel="noopener">Open Payment</a>
                <a class="btn btn-secondary" href="#" onclick="window.location.reload(); return false;">Reload</a>
            </div>
        @endif
    </div>
</div>
</body>
</html>
