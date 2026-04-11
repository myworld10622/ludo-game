<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Play Ludo</title>
    <style>
        :root {
            --bg: #07131f;
            --panel: #0f2236;
            --panel-soft: rgba(255,255,255,0.06);
            --line: rgba(255,255,255,0.12);
            --text: #f4f8fc;
            --muted: #afc0d2;
            --accent: #f8c537;
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
                radial-gradient(circle at top, rgba(74, 150, 255, 0.18), transparent 24%),
                linear-gradient(180deg, #07111c 0%, #0b1c2e 100%);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .page {
            width: min(1320px, calc(100% - 24px));
            margin: 0 auto;
            padding: 18px 0 24px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
            padding: 14px 18px;
            border-radius: 18px;
            border: 1px solid var(--line);
            background: rgba(255,255,255,0.04);
        }

        .back-link {
            color: var(--muted);
            font-weight: 700;
        }

        .badge {
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(248, 197, 55, 0.12);
            color: #ffe29e;
            font-size: 0.9rem;
        }

        .shell {
            border: 1px solid var(--line);
            border-radius: 24px;
            overflow: hidden;
            background: var(--panel);
        }

        .shell-head {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 18px;
            background: rgba(255,255,255,0.04);
            border-bottom: 1px solid var(--line);
        }

        .shell-head p {
            margin: 4px 0 0;
            color: var(--muted);
        }

        .game-wrap {
            position: relative;
            width: 100%;
            aspect-ratio: 16 / 9;
            min-height: 520px;
            background: #02070d;
        }

        #unity-canvas {
            width: 100%;
            height: 100%;
            display: block;
            background: #02070d;
        }

        .overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg, rgba(6, 14, 23, 0.72), rgba(6, 14, 23, 0.88));
            z-index: 2;
        }

        .loader-card {
            width: min(460px, calc(100% - 32px));
            padding: 28px;
            border-radius: 22px;
            border: 1px solid var(--line);
            background: rgba(9, 21, 35, 0.88);
            text-align: center;
        }

        .loader-card h2 {
            margin: 0 0 10px;
        }

        .loader-card p {
            margin: 0 0 18px;
            color: var(--muted);
        }

        .progress {
            width: 100%;
            height: 14px;
            border-radius: 999px;
            overflow: hidden;
            background: rgba(255,255,255,0.1);
        }

        .progress > span {
            display: block;
            width: 0;
            height: 100%;
            background: linear-gradient(90deg, var(--accent), #ff8f4d);
            transition: width 0.2s ease;
        }

        .status {
            margin-top: 12px;
            color: #ffe6aa;
            font-size: 0.95rem;
        }

        .error-box {
            margin-top: 14px;
            padding: 12px 14px;
            border-radius: 14px;
            background: rgba(255, 107, 95, 0.12);
            color: #ffc7c2;
            font-size: 0.92rem;
            display: none;
            text-align: left;
            white-space: pre-wrap;
        }

        @media (max-width: 768px) {
            .game-wrap {
                min-height: 72vh;
            }

            .shell-head {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <div class="topbar">
            <a class="back-link" href="<?php echo e($landingUrl); ?>">Back to Home</a>
            <span class="badge">WebGL Ludo</span>
        </div>

        <section class="shell">
            <div class="shell-head">
                <div>
                    <strong>Betzono Ludo</strong>
                    <p>Browser edition powered by Unity WebGL.</p>
                </div>
                <div>
                    <strong>Build Status</strong>
                    <p id="build-state">Preparing game files...</p>
                </div>
            </div>

            <div class="game-wrap">
                <canvas id="unity-canvas"></canvas>

                <div class="overlay" id="loading-overlay">
                    <div class="loader-card">
                        <h2>Loading Ludo</h2>
                        <p>Unity WebGL can take a little time on first load. Please keep this tab open.</p>
                        <div class="progress"><span id="progress-bar"></span></div>
                        <div class="status" id="progress-text">Starting...</div>
                        <div class="error-box" id="error-box"></div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        const buildBaseUrl = <?php echo json_encode($buildBaseUrl, 15, 512) ?>;
        const loaderUrl = `${buildBaseUrl}/ludo_build.loader.js`;
        const config = {
            dataUrl: `${buildBaseUrl}/ludo_build.data.gz`,
            frameworkUrl: `${buildBaseUrl}/ludo_build.framework.js.gz`,
            codeUrl: `${buildBaseUrl}/ludo_build.wasm.gz`,
            streamingAssetsUrl: `${buildBaseUrl}/StreamingAssets`,
            companyName: "Betzono",
            productName: "Ludo",
            productVersion: "1.0"
        };

        const canvas = document.getElementById("unity-canvas");
        const progressBar = document.getElementById("progress-bar");
        const progressText = document.getElementById("progress-text");
        const buildState = document.getElementById("build-state");
        const loadingOverlay = document.getElementById("loading-overlay");
        const errorBox = document.getElementById("error-box");

        function showError(message) {
            errorBox.style.display = "block";
            errorBox.textContent = message;
            buildState.textContent = "Failed to load";
        }

        const script = document.createElement("script");
        script.src = loaderUrl;
        script.onload = () => {
            createUnityInstance(canvas, config, (progress) => {
                const percent = Math.round(progress * 100);
                progressBar.style.width = `${percent}%`;
                progressText.textContent = `Loading ${percent}%`;
                buildState.textContent = `Loading ${percent}%`;
            }).then(() => {
                loadingOverlay.style.display = "none";
                buildState.textContent = "Live";
            }).catch((error) => {
                showError(`Unity error: ${error}`);
            });
        };
        script.onerror = () => {
            showError("Loader file could not be fetched. Check whether WebGL build files were copied into public/ludo-webgl/Build.");
        };
        document.body.appendChild(script);
    </script>
</body>
</html>
<?php /**PATH D:\Live-Code\Live-Rox-Ludo\games\backend_laravel\resources\views/ludo/play.blade.php ENDPATH**/ ?>