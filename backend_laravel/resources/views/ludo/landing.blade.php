<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ludo Betzono</title>
    <style>
        :root {
            --bg: #08131f;
            --bg-soft: #10243a;
            --panel: rgba(7, 18, 31, 0.76);
            --line: rgba(255, 255, 255, 0.12);
            --text: #f7fbff;
            --muted: #b8c7d9;
            --accent: #f8c537;
            --accent-2: #41d1a8;
            --danger: #ff6b5f;
            --shadow: 0 30px 80px rgba(0, 0, 0, 0.35);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(248, 197, 55, 0.22), transparent 30%),
                radial-gradient(circle at top right, rgba(65, 209, 168, 0.18), transparent 26%),
                linear-gradient(145deg, #07111b 0%, #0d1f33 48%, #091521 100%);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .hero {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
            padding: 42px 0 56px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
        }

        .brand-mark {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--danger));
            box-shadow: 16px 0 0 var(--accent-2), 8px 13px 0 #4ea3ff;
            margin-right: 14px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1.2fr 0.95fr;
            gap: 28px;
            align-items: stretch;
            margin-top: 30px;
        }

        .card {
            border: 1px solid var(--line);
            background: var(--panel);
            backdrop-filter: blur(14px);
            box-shadow: var(--shadow);
            border-radius: 28px;
            overflow: hidden;
        }

        .content {
            padding: 42px;
        }

        .eyebrow {
            display: inline-block;
            margin-bottom: 18px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(248, 197, 55, 0.12);
            color: #ffe29e;
            font-size: 13px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0 0 16px;
            font-size: clamp(2.8rem, 5vw, 5.4rem);
            line-height: 0.95;
            letter-spacing: -0.04em;
        }

        .lead {
            max-width: 720px;
            margin: 0 0 26px;
            color: var(--muted);
            font-size: 1.05rem;
            line-height: 1.7;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 28px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 180px;
            padding: 16px 24px;
            border-radius: 16px;
            font-weight: 700;
            letter-spacing: 0.01em;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent), #ff9f43);
            color: #1e1704;
            box-shadow: 0 14px 28px rgba(248, 197, 55, 0.28);
        }

        .btn-secondary {
            border: 1px solid rgba(255, 255, 255, 0.18);
            background: rgba(255, 255, 255, 0.06);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .stat {
            padding: 18px;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.04);
        }

        .stat strong {
            display: block;
            margin-bottom: 6px;
            font-size: 1.25rem;
        }

        .stat span {
            color: var(--muted);
            font-size: 0.92rem;
        }

        .board {
            min-height: 100%;
            position: relative;
            background:
                linear-gradient(180deg, rgba(255,255,255,0.05), rgba(255,255,255,0.02)),
                linear-gradient(140deg, #11253b 0%, #0a1522 100%);
        }

        .board-inner {
            display: flex;
            min-height: 100%;
            align-items: center;
            justify-content: center;
            padding: 34px;
        }

        .ludo-shell {
            width: min(100%, 420px);
            aspect-ratio: 1;
            border-radius: 28px;
            padding: 18px;
            background: #f6f0e2;
            box-shadow: inset 0 0 0 8px #203448, 0 20px 44px rgba(0, 0, 0, 0.28);
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(3, 1fr);
            gap: 12px;
        }

        .zone {
            border-radius: 22px;
            position: relative;
            overflow: hidden;
            display: grid;
            place-items: center;
        }

        .zone.red { background: #ff7266; }
        .zone.green { background: #3ccf8e; }
        .zone.yellow { background: #f4c542; }
        .zone.blue { background: #4a96ff; }
        .zone.center {
            background: linear-gradient(135deg, #213447, #122335);
            color: white;
            font-weight: 800;
            text-align: center;
            padding: 14px;
            font-size: 1.1rem;
        }

        .tokens {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .token {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.88);
            border: 4px solid rgba(0, 0, 0, 0.14);
            box-shadow: inset 0 -5px 8px rgba(0, 0, 0, 0.15);
        }

        .note {
            margin-top: 22px;
            color: var(--muted);
            font-size: 0.92rem;
        }

        @media (max-width: 960px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .content {
                padding: 32px 24px;
            }

            .stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <main class="hero">
        <div class="brand">
            <span class="brand-mark"></span>
            <strong>Betzono Ludo</strong>
        </div>

        <section class="grid">
            <div class="card">
                <div class="content">
                    <span class="eyebrow">Play Anywhere</span>
                    <h1>Roll fast. Clash hard. Win big.</h1>
                    <p class="lead">
                        Betzono Ludo brings the classic board race into a sharp competitive arena.
                        Jump into the browser instantly or install the Android app for a smoother mobile experience.
                    </p>

                    <div class="actions">
                        <a class="btn btn-primary" href="{{ $playUrl }}">Play Ludo</a>
                        <a class="btn btn-secondary" href="{{ $apkUrl }}">Download APK</a>
                    </div>

                    <div class="stats">
                        <div class="stat">
                            <strong>Instant Launch</strong>
                            <span>Open the WebGL version directly in your browser.</span>
                        </div>
                        <div class="stat">
                            <strong>APK Ready</strong>
                            <span>Install on Android for a native app-style experience.</span>
                        </div>
                        <div class="stat">
                            <strong>Fast Matches</strong>
                            <span>Quick access from the landing page to gameplay.</span>
                        </div>
                    </div>

                    <p class="note">
                        Tip: if your APK is hosted at a different URL, update the `LUDO_APK_URL` env value.
                    </p>
                </div>
            </div>

            <aside class="card board">
                <div class="board-inner">
                    <div>
                        <div class="ludo-shell">
                            <div class="zone red"><div class="tokens"><span class="token"></span><span class="token"></span><span class="token"></span><span class="token"></span></div></div>
                            <div class="zone center">BETZONO<br>LUDO</div>
                            <div class="zone green"><div class="tokens"><span class="token"></span><span class="token"></span><span class="token"></span><span class="token"></span></div></div>
                            <div class="zone center">PLAY</div>
                            <div class="zone center">NOW</div>
                            <div class="zone center">ONLINE</div>
                            <div class="zone blue"><div class="tokens"><span class="token"></span><span class="token"></span><span class="token"></span><span class="token"></span></div></div>
                            <div class="zone center">RACE</div>
                            <div class="zone yellow"><div class="tokens"><span class="token"></span><span class="token"></span><span class="token"></span><span class="token"></span></div></div>
                        </div>
                    </div>
                </div>
            </aside>
        </section>
    </main>
</body>
</html>
