<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RoxLudo — Tournament Arena</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Exo+2:wght@300;400;600;800;900&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

  :root {
    --gold: #FFD700;
    --gold-light: #FFE566;
    --gold-dark: #CC9900;
    --red: #E63946;
    --blue: #1A6BFF;
    --green: #06D6A0;
    --purple: #7B2FBE;
    --bg-dark: #0A0A14;
    --bg-card: #12121F;
    --bg-card2: #1A1A2E;
    --text: #F0F0FF;
    --text-muted: #8888AA;
    --border: rgba(255,215,0,0.15);
  }

  html { scroll-behavior: smooth; }

  body {
    font-family: 'Exo 2', sans-serif;
    background: var(--bg-dark);
    color: var(--text);
    min-height: 100vh;
    overflow-x: hidden;
  }

  /* ── GRID BACKGROUND ── */
  body::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image:
      linear-gradient(rgba(255,215,0,0.03) 1px, transparent 1px),
      linear-gradient(90deg, rgba(255,215,0,0.03) 1px, transparent 1px);
    background-size: 60px 60px;
    pointer-events: none;
    z-index: 0;
  }

  /* ── NAVBAR ── */
  nav {
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 100;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 5%;
    height: 70px;
    background: rgba(10,10,20,0.85);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--border);
  }

  .nav-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
  }

  .nav-logo img {
    height: 42px;
    filter: drop-shadow(0 0 12px rgba(255,215,0,0.5));
  }

  .logo-fallback {
    width: 42px; height: 42px;
    background: linear-gradient(135deg, var(--gold), var(--red));
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Orbitron', sans-serif;
    font-size: 18px; font-weight: 900;
    color: #000;
    filter: drop-shadow(0 0 12px rgba(255,215,0,0.5));
  }

  .nav-brand {
    font-family: 'Orbitron', sans-serif;
    font-size: 18px;
    font-weight: 700;
    background: linear-gradient(135deg, var(--gold), #FF6B6B);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    letter-spacing: 1px;
  }

  .nav-links {
    display: flex;
    align-items: center;
    gap: 8px;
    list-style: none;
  }

  .nav-links a {
    text-decoration: none;
    color: var(--text-muted);
    font-size: 14px;
    font-weight: 500;
    padding: 8px 14px;
    border-radius: 8px;
    transition: all 0.2s;
    letter-spacing: 0.5px;
  }
  .nav-links a:hover { color: var(--gold); background: rgba(255,215,0,0.08); }

  @media (max-width: 768px) {
    .nav-links { display: none; }
  }

  .btn-login {
    background: linear-gradient(135deg, var(--gold), var(--gold-dark)) !important;
    color: #000 !important;
    font-weight: 700 !important;
    padding: 9px 22px !important;
    border-radius: 10px !important;
    letter-spacing: 0.5px;
    transition: all 0.2s !important;
    box-shadow: 0 0 20px rgba(255,215,0,0.3);
  }
  .btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 0 30px rgba(255,215,0,0.5) !important;
    background: rgba(0,0,0,0) !important;
  }

  .lang-toggle {
    display: inline-flex;
    gap: 6px;
    margin-right: 10px;
    padding: 4px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 10px;
  }
  .lang-btn {
    background: transparent;
    border: none;
    color: var(--text-muted);
    padding: 6px 10px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.5px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
  }
  .lang-btn.active {
    background: rgba(255,215,0,0.15);
    color: var(--gold);
  }

  /* ── HERO ── */
  .hero {
    position: relative;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 120px 5% 80px;
    overflow: hidden;
    z-index: 1;
  }

  .hero::after {
    content: '';
    position: absolute;
    width: 600px; height: 600px;
    background: radial-gradient(circle, rgba(255,215,0,0.08) 0%, transparent 70%);
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    pointer-events: none;
  }

  .orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    pointer-events: none;
  }
  .orb-1 { width: 400px; height: 400px; background: rgba(230,57,70,0.12); top: -100px; right: -100px; }
  .orb-2 { width: 300px; height: 300px; background: rgba(26,107,255,0.1); bottom: 0; left: -80px; }
  .orb-3 { width: 200px; height: 200px; background: rgba(123,47,190,0.12); top: 40%; right: 10%; }

  .hero-content {
    text-align: center;
    max-width: 900px;
    position: relative;
    z-index: 2;
  }

  .hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,215,0,0.1);
    border: 1px solid rgba(255,215,0,0.3);
    color: var(--gold);
    font-size: 13px;
    font-weight: 600;
    padding: 6px 16px;
    border-radius: 50px;
    margin-bottom: 28px;
    letter-spacing: 1px;
    text-transform: uppercase;
  }

  .hero-badge span { font-size: 16px; }

  h1 {
    font-family: 'Orbitron', sans-serif;
    font-size: clamp(42px, 7vw, 90px);
    font-weight: 900;
    line-height: 1.0;
    margin-bottom: 10px;
    letter-spacing: -1px;
  }

  .h1-line1 {
    background: linear-gradient(135deg, #fff 0%, var(--gold) 60%, #FF6B6B 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    display: block;
  }

  .h1-line2 {
    background: linear-gradient(135deg, var(--gold) 0%, var(--gold-light) 50%, #FFA07A 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    display: block;
    font-size: clamp(32px, 5.5vw, 70px);
  }

  .hero-sub {
    font-size: clamp(15px, 2vw, 18px);
    color: var(--text-muted);
    max-width: 580px;
    margin: 24px auto 44px;
    line-height: 1.7;
    font-weight: 300;
  }

  .hero-sub strong { color: var(--gold); font-weight: 600; }

  .hero-cta {
    display: flex;
    gap: 16px;
    justify-content: center;
    flex-wrap: wrap;
  }

  .btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: linear-gradient(135deg, var(--gold), #FF9500);
    color: #000;
    font-family: 'Rajdhani', sans-serif;
    font-size: 17px;
    font-weight: 700;
    padding: 15px 36px;
    border-radius: 14px;
    text-decoration: none;
    letter-spacing: 1px;
    text-transform: uppercase;
    transition: all 0.3s;
    box-shadow: 0 8px 32px rgba(255,215,0,0.35);
    border: none;
    cursor: pointer;
  }
  .btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 48px rgba(255,215,0,0.5);
  }

  .btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: transparent;
    color: var(--text);
    font-family: 'Rajdhani', sans-serif;
    font-size: 17px;
    font-weight: 600;
    padding: 14px 32px;
    border-radius: 14px;
    text-decoration: none;
    letter-spacing: 1px;
    text-transform: uppercase;
    transition: all 0.3s;
    border: 1px solid rgba(255,255,255,0.2);
    cursor: pointer;
  }
  .btn-secondary:hover {
    border-color: var(--gold);
    color: var(--gold);
    background: rgba(255,215,0,0.06);
    transform: translateY(-2px);
  }

  .btn-apk {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: linear-gradient(135deg, #06D6A0, #028A5E);
    color: #fff;
    font-family: 'Rajdhani', sans-serif;
    font-size: 17px;
    font-weight: 700;
    padding: 15px 32px;
    border-radius: 14px;
    text-decoration: none;
    letter-spacing: 1px;
    text-transform: uppercase;
    transition: all 0.3s;
    box-shadow: 0 8px 32px rgba(6,214,160,0.3);
    border: none;
    cursor: pointer;
  }
  .btn-apk:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 48px rgba(6,214,160,0.5);
  }

  /* ── LUDO BOARD VISUAL ── */
  .board-visual {
    margin-top: 70px;
    position: relative;
    display: flex;
    justify-content: center;
  }

  .board-3d {
    width: 280px; height: 280px;
    position: relative;
    transform: perspective(800px) rotateX(20deg) rotateZ(-5deg);
    transition: transform 0.5s ease;
  }
  .board-3d:hover { transform: perspective(800px) rotateX(10deg) rotateZ(0deg); }

  .board-grid {
    width: 100%; height: 100%;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    grid-template-rows: repeat(3, 1fr);
    gap: 4px;
    background: var(--bg-card2);
    border-radius: 20px;
    padding: 12px;
    box-shadow:
      0 0 0 1px rgba(255,215,0,0.2),
      0 30px 80px rgba(0,0,0,0.8),
      0 0 60px rgba(255,215,0,0.1);
  }

  .board-cell {
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
  }

  .cell-red { background: linear-gradient(135deg, #E63946, #9B111E); }
  .cell-blue { background: linear-gradient(135deg, #1A6BFF, #0033AA); }
  .cell-green { background: linear-gradient(135deg, #06D6A0, #028A5E); }
  .cell-yellow { background: linear-gradient(135deg, #FFD700, #CC7700); }
  .cell-center {
    background: linear-gradient(135deg, #1A1A2E, #0A0A14);
    border: 1px solid rgba(255,215,0,0.3);
    font-size: 30px;
  }
  .cell-path { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.08); }

  .board-glow {
    position: absolute;
    inset: -30px;
    background: radial-gradient(circle, rgba(255,215,0,0.15) 0%, transparent 65%);
    pointer-events: none;
    border-radius: 50%;
  }

  /* ── STATS STRIP ── */
  .stats-strip {
    position: relative;
    z-index: 1;
    background: var(--bg-card);
    border-top: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
    padding: 28px 5%;
  }

  .stats-inner {
    max-width: 1100px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    text-align: center;
  }

  .stat-item { padding: 10px; }

  .stat-num {
    font-family: 'Orbitron', sans-serif;
    font-size: clamp(28px, 4vw, 44px);
    font-weight: 700;
    background: linear-gradient(135deg, var(--gold), #FF9500);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1;
  }

  .stat-label {
    font-size: 13px;
    color: var(--text-muted);
    margin-top: 6px;
    font-weight: 500;
    letter-spacing: 0.5px;
    text-transform: uppercase;
  }

  /* ── SECTIONS ── */
  section {
    position: relative;
    z-index: 1;
    padding: 90px 5%;
  }

  .section-inner { max-width: 1100px; margin: 0 auto; }

  .section-label {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 12px;
  }

  .section-title {
    font-family: 'Exo 2', sans-serif;
    font-size: clamp(28px, 4vw, 46px);
    font-weight: 800;
    line-height: 1.15;
    margin-bottom: 16px;
  }

  .section-desc {
    color: var(--text-muted);
    font-size: 16px;
    max-width: 560px;
    line-height: 1.7;
  }

  /* ── TOURNAMENT CARDS ── */
  .tournaments-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 50px;
  }

  .t-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 28px;
    position: relative;
    overflow: hidden;
    transition: all 0.3s;
    cursor: pointer;
  }
  .t-card:hover {
    transform: translateY(-6px);
    border-color: rgba(255,215,0,0.4);
    box-shadow: 0 20px 60px rgba(0,0,0,0.5), 0 0 30px rgba(255,215,0,0.1);
  }

  .t-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
  }
  .t-card.gold::before { background: linear-gradient(90deg, var(--gold), #FF9500); }
  .t-card.blue::before { background: linear-gradient(90deg, var(--blue), #00C3FF); }
  .t-card.purple::before { background: linear-gradient(90deg, var(--purple), #C060FF); }

  .t-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    padding: 4px 12px;
    border-radius: 50px;
    margin-bottom: 16px;
  }
  .t-badge.live { background: rgba(230,57,70,0.15); color: var(--red); border: 1px solid rgba(230,57,70,0.3); }
  .t-badge.open { background: rgba(6,214,160,0.12); color: var(--green); border: 1px solid rgba(6,214,160,0.3); }
  .t-badge.soon { background: rgba(26,107,255,0.12); color: #66AAFF; border: 1px solid rgba(26,107,255,0.3); }

  .live-dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    background: var(--red);
    animation: pulse 1.5s infinite;
  }
  @keyframes pulse { 0%,100% { opacity:1; } 50% { opacity: 0.3; } }

  .t-name {
    font-family: 'Rajdhani', sans-serif;
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 8px;
    letter-spacing: 0.5px;
  }

  .t-desc { font-size: 14px; color: var(--text-muted); margin-bottom: 20px; line-height: 1.6; }

  .t-meta {
    display: flex;
    gap: 20px;
    padding-top: 18px;
    border-top: 1px solid rgba(255,255,255,0.06);
  }

  .t-meta-item { display: flex; flex-direction: column; gap: 3px; }
  .t-meta-label { font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
  .t-meta-val { font-size: 16px; font-weight: 700; color: var(--gold); font-family: 'Rajdhani', sans-serif; }

  /* ── FEATURES ── */
  .features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 20px;
    margin-top: 50px;
  }

  .feature-card {
    background: var(--bg-card);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 18px;
    padding: 30px;
    transition: all 0.3s;
  }
  .feature-card:hover {
    border-color: var(--border);
    transform: translateY(-4px);
    box-shadow: 0 16px 40px rgba(0,0,0,0.4);
  }

  .feature-icon {
    width: 54px; height: 54px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    margin-bottom: 18px;
  }
  .fi-gold { background: rgba(255,215,0,0.12); }
  .fi-red { background: rgba(230,57,70,0.12); }
  .fi-blue { background: rgba(26,107,255,0.12); }
  .fi-green { background: rgba(6,214,160,0.12); }

  .feature-title {
    font-family: 'Rajdhani', sans-serif;
    font-size: 19px;
    font-weight: 700;
    margin-bottom: 10px;
    letter-spacing: 0.3px;
  }

  .feature-desc { font-size: 14px; color: var(--text-muted); line-height: 1.65; }

  /* ── HOW TO PLAY ── */
  .howto-section { background: var(--bg-card); }

  .steps-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 30px;
    margin-top: 50px;
  }

  .step {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 16px;
    padding: 30px 20px;
    background: var(--bg-dark);
    border: 1px solid rgba(255,255,255,0.05);
    border-radius: 18px;
    transition: all 0.3s;
  }
  .step:hover { border-color: var(--border); transform: translateY(-3px); }

  .step-num {
    width: 52px; height: 52px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--gold), #FF9500);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Orbitron', sans-serif;
    font-size: 20px;
    font-weight: 700;
    color: #000;
    box-shadow: 0 0 24px rgba(255,215,0,0.3);
    flex-shrink: 0;
  }

  .step-icon { font-size: 28px; }

  .step-title {
    font-family: 'Rajdhani', sans-serif;
    font-size: 18px;
    font-weight: 700;
    letter-spacing: 0.3px;
  }

  .step-desc { font-size: 13px; color: var(--text-muted); line-height: 1.6; }

  /* ── GUIDE & FAQ ── */
  .guide-faq {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-top: 50px;
  }

  @media (max-width: 768px) { .guide-faq { grid-template-columns: 1fr; } }

  .guide-card {
    background: linear-gradient(135deg, rgba(255,215,0,0.05), rgba(255,150,0,0.05));
    border: 1px solid rgba(255,215,0,0.2);
    border-radius: 22px;
    padding: 36px;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
    transition: all 0.3s;
  }
  .guide-card:hover {
    border-color: rgba(255,215,0,0.4);
    box-shadow: 0 20px 60px rgba(0,0,0,0.4), 0 0 40px rgba(255,215,0,0.08);
    transform: translateY(-4px);
  }

  .faq-card {
    background: linear-gradient(135deg, rgba(26,107,255,0.05), rgba(123,47,190,0.05));
    border: 1px solid rgba(26,107,255,0.2);
    border-radius: 22px;
    padding: 36px;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
    transition: all 0.3s;
  }
  .faq-card:hover {
    border-color: rgba(26,107,255,0.4);
    box-shadow: 0 20px 60px rgba(0,0,0,0.4), 0 0 40px rgba(26,107,255,0.08);
    transform: translateY(-4px);
  }

  .gc-icon {
    width: 60px; height: 60px;
    border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    font-size: 30px;
  }
  .gc-icon.gold-bg { background: rgba(255,215,0,0.12); }
  .gc-icon.blue-bg { background: rgba(26,107,255,0.12); }

  .gc-title {
    font-family: 'Rajdhani', sans-serif;
    font-size: 24px;
    font-weight: 700;
    letter-spacing: 0.5px;
  }

  .gc-desc { font-size: 15px; color: var(--text-muted); line-height: 1.65; }

  /* ── FAQ ACCORDION ── */
  .faq-section { padding-top: 0; }

  .faq-list { margin-top: 50px; display: flex; flex-direction: column; gap: 10px; }

  .faq-item {
    background: var(--bg-card);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 14px;
    overflow: hidden;
    transition: border-color 0.2s;
  }
  .faq-item.open { border-color: rgba(255,215,0,0.25); }

  .faq-q {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    gap: 16px;
    transition: color 0.2s;
  }
  .faq-item.open .faq-q { color: var(--gold); }

  .faq-icon {
    width: 28px; height: 28px;
    border-radius: 50%;
    background: rgba(255,255,255,0.06);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
    transition: all 0.3s;
    color: var(--text-muted);
  }
  .faq-item.open .faq-icon {
    background: rgba(255,215,0,0.12);
    color: var(--gold);
    transform: rotate(45deg);
  }

  .faq-a {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.35s ease, padding 0.2s;
    font-size: 14px;
    color: var(--text-muted);
    line-height: 1.7;
    padding: 0 24px;
  }
  .faq-item.open .faq-a { max-height: 300px; padding: 0 24px 20px; }

  /* ── MODAL ── */
  .modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.85);
    backdrop-filter: blur(10px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }
  .modal-overlay.show { display: flex; }

  .modal {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 24px;
    max-width: 700px;
    width: 100%;
    max-height: 85vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 40px 120px rgba(0,0,0,0.8), 0 0 60px rgba(255,215,0,0.1);
  }

  .modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 28px 32px 20px;
    border-bottom: 1px solid var(--border);
    position: sticky;
    top: 0;
    background: var(--bg-card);
    border-radius: 24px 24px 0 0;
    z-index: 2;
  }

  .modal-title {
    font-family: 'Rajdhani', sans-serif;
    font-size: 22px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .modal-close {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: rgba(255,255,255,0.06);
    border: none;
    color: var(--text-muted);
    font-size: 20px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.2s;
  }
  .modal-close:hover { background: rgba(230,57,70,0.2); color: var(--red); }

  .modal-body { padding: 24px 32px 32px; }

  .modal-body h3 {
    font-family: 'Rajdhani', sans-serif;
    font-size: 18px;
    font-weight: 700;
    margin: 20px 0 8px;
    color: var(--gold);
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .modal-body p { font-size: 15px; color: var(--text-muted); line-height: 1.7; margin-bottom: 12px; }

  .modal-body ul { padding-left: 20px; color: var(--text-muted); font-size: 14px; line-height: 1.8; }

  /* ── LOGIN MODAL ── */
  .login-modal { max-width: 440px; }

  .login-form { display: flex; flex-direction: column; gap: 16px; }

  .form-group { display: flex; flex-direction: column; gap: 8px; }

  .form-label { font-size: 13px; font-weight: 600; letter-spacing: 0.5px; color: var(--text-muted); text-transform: uppercase; }

  .form-input {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    padding: 13px 16px;
    color: var(--text);
    font-family: 'Exo 2', sans-serif;
    font-size: 15px;
    outline: none;
    transition: all 0.2s;
    width: 100%;
  }
  .form-input:focus { border-color: var(--gold); background: rgba(255,215,0,0.04); }
  .form-input::placeholder { color: rgba(255,255,255,0.25); }

  .form-submit {
    background: linear-gradient(135deg, var(--gold), #FF9500);
    color: #000;
    font-family: 'Rajdhani', sans-serif;
    font-size: 17px;
    font-weight: 700;
    padding: 15px;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    letter-spacing: 1px;
    text-transform: uppercase;
    transition: all 0.2s;
    margin-top: 6px;
    box-shadow: 0 8px 24px rgba(255,215,0,0.3);
  }
  .form-submit:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(255,215,0,0.4); }

  .login-divider {
    text-align: center;
    position: relative;
    color: var(--text-muted);
    font-size: 13px;
    margin: 4px 0;
  }
  .login-divider::before {
    content: '';
    position: absolute;
    top: 50%; left: 0; right: 0;
    height: 1px;
    background: rgba(255,255,255,0.08);
  }
  .login-divider span { background: var(--bg-card); padding: 0 12px; position: relative; }

  .login-register {
    text-align: center;
    font-size: 14px;
    color: var(--text-muted);
  }
  .login-register a { color: var(--gold); text-decoration: none; font-weight: 600; }
  .login-register a:hover { text-decoration: underline; }

  /* ── FOOTER ── */
  footer {
    position: relative;
    z-index: 1;
    background: var(--bg-card);
    border-top: 1px solid var(--border);
    padding: 50px 5% 30px;
  }

  .footer-inner { max-width: 1100px; margin: 0 auto; }

  .footer-top {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr;
    gap: 40px;
    padding-bottom: 40px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
  }

  @media (max-width: 768px) {
    .footer-top { grid-template-columns: 1fr 1fr; }
    .stats-inner { grid-template-columns: repeat(2, 1fr); }
    h1 { font-size: 38px; }
  }

  .footer-brand p {
    font-size: 14px;
    color: var(--text-muted);
    margin-top: 12px;
    line-height: 1.7;
    max-width: 260px;
  }

  .footer-col h4 {
    font-family: 'Rajdhani', sans-serif;
    font-size: 16px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 16px;
    color: var(--gold);
  }

  .footer-col a {
    display: block;
    text-decoration: none;
    color: var(--text-muted);
    font-size: 14px;
    margin-bottom: 10px;
    transition: color 0.2s;
  }
  .footer-col a:hover { color: var(--text); }

  .footer-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 24px;
    font-size: 13px;
    color: var(--text-muted);
    flex-wrap: wrap;
    gap: 12px;
  }

  /* ── SCROLL ANIMATIONS ── */
  .reveal {
    opacity: 0;
    transform: translateY(30px);
    transition: opacity 0.7s ease, transform 0.7s ease;
  }
  .reveal.visible { opacity: 1; transform: translateY(0); }

  /* ── CURSOR GLOW ── */
  .cursor-glow {
    pointer-events: none;
    position: fixed;
    width: 300px; height: 300px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255,215,0,0.06) 0%, transparent 70%);
    transform: translate(-50%, -50%);
    z-index: 9999;
    transition: opacity 0.3s;
  }
</style>
</head>
<body>

<div class="cursor-glow" id="cursorGlow"></div>

<!-- MODALS -->
<div class="modal-overlay" id="loginModal">
  <div class="modal login-modal">
    <div class="modal-header">
      <div class="modal-title">🎲 Player Login</div>
      <button class="modal-close" onclick="closeModal('loginModal')">✕</button>
    </div>
    <div class="modal-body">
      <div class="login-form">
        <div class="form-group">
          <label class="form-label">Username / Email</label>
          <input class="form-input" type="text" placeholder="Enter your username or email">
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input class="form-input" type="password" placeholder="••••••••">
        </div>
        <button class="form-submit">🎯 Enter the Arena</button>
        <div class="login-divider"><span>New Player?</span></div>
        <div class="login-register">Don't have an account? <a href="#">Register Free</a></div>
      </div>
    </div>
  </div>
</div>

<div class="modal-overlay" id="guideModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" data-i18n="guide.title">📖 How to Play — Complete Guide</div>
      <button class="modal-close" onclick="closeModal('guideModal')">✕</button>
    </div>
    <div class="modal-body" id="guideContent">
      <h3 data-i18n="guide.basic.title">🎲 Basic Rules</h3>
      <p data-i18n="guide.basic.desc">Ludo is a classic board game with 2–4 players. Each player must move all 4 tokens from base to home.</p>
      <h3 data-i18n="guide.start.title">🎯 Game Start</h3>
      <ul>
        <li data-i18n="guide.start.items.1">Roll a 6 to bring a token onto the board.</li>
        <li data-i18n="guide.start.items.2">Move your token based on the dice roll.</li>
        <li data-i18n="guide.start.items.3">Capture opponent tokens to send them back to base.</li>
        <li data-i18n="guide.start.items.4">Safe squares protect tokens from capture.</li>
      </ul>
      <h3 data-i18n="guide.tournament.title">🏆 Tournament Rules</h3>
      <ul>
        <li data-i18n="guide.tournament.items.1">Anyone can join public tournaments.</li>
        <li data-i18n="guide.tournament.items.2">Private tournaments are invite-only.</li>
        <li data-i18n="guide.tournament.items.3">Winners receive the prize pool.</li>
        <li data-i18n="guide.tournament.items.4">Fair play rules are strictly enforced.</li>
      </ul>
      <h3 data-i18n="guide.create.title">🎪 Create Tournament</h3>
      <p data-i18n="guide.create.desc">Go to the dashboard, click “Create Tournament,” choose Public/Private, set entry fee and prizes, then share the link.</p>
      <h3 data-i18n="guide.prize.title">💰 Prize Pool</h3>
      <ul>
        <li data-i18n="guide.prize.items.1">Winner receives 70% of the prize pool.</li>
        <li data-i18n="guide.prize.items.2">Runner-up receives 20%.</li>
        <li data-i18n="guide.prize.items.3">Platform fee: 10%.</li>
      </ul>
      <p style="color: var(--gold); margin-top: 16px; font-weight: 600;" data-i18n="guide.note">For a full guide, see docs/ludo-user-guide-hinglish.html.</p>
    </div>
  </div>
</div>

<div class="modal-overlay" id="faqModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" data-i18n="faq.title">❓ Frequently Asked Questions</div>
      <button class="modal-close" onclick="closeModal('faqModal')">✕</button>
    </div>
    <div class="modal-body">
      <div class="faq-list">
        <div class="faq-item">
          <div class="faq-q" onclick="toggleFaqModal(this)"><span data-i18n="faq.q1">Is the game free to play?</span><div class="faq-icon">+</div></div>
          <div class="faq-a" data-i18n="faq.a1">Yes. The basic game is free with practice rooms and free tournaments. Prize tournaments have an entry fee that goes into the prize pool.</div>
        </div>
        <div class="faq-item">
          <div class="faq-q" onclick="toggleFaqModal(this)"><span data-i18n="faq.q2">How do I create a private tournament?</span><div class="faq-icon">+</div></div>
          <div class="faq-a" data-i18n="faq.a2">Dashboard → Create Tournament → select Private → set password/invite link → share with friends. Only invited players can join.</div>
        </div>
        <div class="faq-item">
          <div class="faq-q" onclick="toggleFaqModal(this)"><span data-i18n="faq.q3">How do I withdraw winnings?</span><div class="faq-icon">+</div></div>
          <div class="faq-a" data-i18n="faq.a3">Profile → Wallet → Withdraw → add UPI/Bank details. Funds transfer in 24–48 hours. Minimum withdrawal is ₹50.</div>
        </div>
        <div class="faq-item">
          <div class="faq-q" onclick="toggleFaqModal(this)"><span data-i18n="faq.q4">Is cheating possible in the game?</span><div class="faq-icon">+</div></div>
          <div class="faq-a" data-i18n="faq.a4">No. Our anti-cheat system runs 24/7. Dice rolls are verified random. Suspicious activity can lead to a ban.</div>
        </div>
        <div class="faq-item">
          <div class="faq-q" onclick="toggleFaqModal(this)"><span data-i18n="faq.q5">How many players can play together?</span><div class="faq-icon">+</div></div>
          <div class="faq-a" data-i18n="faq.a5">Classic Ludo supports 2–4 players. Tournaments can have 8 to 512 players in bracket format. Team mode is also available.</div>
        </div>
        <div class="faq-item">
          <div class="faq-q" onclick="toggleFaqModal(this)"><span data-i18n="faq.q6">Does it work on mobile?</span><div class="faq-icon">+</div></div>
          <div class="faq-a" data-i18n="faq.a6">Yes. The site is fully responsive on Android and iOS. A dedicated app is coming soon.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- APK DOWNLOAD MODAL -->
<div class="modal-overlay" id="apkModal">
  <div class="modal" style="max-width:520px;">
    <div class="modal-header">
      <div class="modal-title" data-i18n="apk.title">📱 Download RoxLudo APK</div>
      <button class="modal-close" onclick="closeModal('apkModal')">✕</button>
    </div>
    <div class="modal-body">
      <div style="text-align:center; padding: 10px 0 24px;">
        <div style="width:80px;height:80px;background:linear-gradient(135deg,#06D6A0,#028A5E);border-radius:20px;display:flex;align-items:center;justify-content:center;font-size:40px;margin:0 auto 16px;">📱</div>
        <h3 style="font-family:'Rajdhani',sans-serif;font-size:22px;font-weight:700;color:var(--text);margin-bottom:8px;" data-i18n="apk.appTitle">RoxLudo Android App</h3>
        <p style="color:var(--text-muted);font-size:14px;" data-i18n="apk.meta">Version 1.0.0 &nbsp;•&nbsp; ~25 MB &nbsp;•&nbsp; Android 6.0+</p>
      </div>
      <a href="{{ $apkUrl }}" download style="display:flex;align-items:center;justify-content:center;gap:12px;background:linear-gradient(135deg,#06D6A0,#028A5E);color:#fff;font-family:'Rajdhani',sans-serif;font-size:18px;font-weight:700;padding:16px;border-radius:14px;text-decoration:none;letter-spacing:1px;text-transform:uppercase;box-shadow:0 8px 24px rgba(6,214,160,0.3);transition:all 0.2s;margin-bottom:28px;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'" data-i18n="apk.cta">⬇️ Download APK</a>

      <div style="background:var(--bg-card2);border:1px solid var(--border);border-radius:16px;padding:24px;">
        <h3 style="font-family:'Rajdhani',sans-serif;font-size:18px;font-weight:700;color:var(--gold);margin-bottom:16px;" data-i18n="apk.guide.title">📋 Android Installation Guide</h3>
        <div style="display:flex;flex-direction:column;gap:14px;">
          <div style="display:flex;gap:14px;align-items:flex-start;">
            <div style="min-width:32px;height:32px;background:linear-gradient(135deg,var(--gold),#FF9500);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:#000;">1</div>
            <div>
              <div style="font-weight:600;font-size:15px;margin-bottom:3px;" data-i18n="apk.guide.step1.title">Download the APK</div>
              <div style="color:var(--text-muted);font-size:13px;" data-i18n="apk.guide.step1.desc">Tap “Download APK.” The file will be saved to your Downloads folder.</div>
            </div>
          </div>
          <div style="display:flex;gap:14px;align-items:flex-start;">
            <div style="min-width:32px;height:32px;background:linear-gradient(135deg,var(--gold),#FF9500);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:#000;">2</div>
            <div>
              <div style="font-weight:600;font-size:15px;margin-bottom:3px;" data-i18n="apk.guide.step2.title">Allow Unknown Sources</div>
              <div style="color:var(--text-muted);font-size:13px;" data-i18n="apk.guide.step2.desc">Settings → Security → Install Unknown Apps → allow Browser/Files (one time).</div>
            </div>
          </div>
          <div style="display:flex;gap:14px;align-items:flex-start;">
            <div style="min-width:32px;height:32px;background:linear-gradient(135deg,var(--gold),#FF9500);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:#000;">3</div>
            <div>
              <div style="font-weight:600;font-size:15px;margin-bottom:3px;" data-i18n="apk.guide.step3.title">Open the APK File</div>
              <div style="color:var(--text-muted);font-size:13px;" data-i18n="apk.guide.step3.desc">Go to Downloads → tap roxludo.apk → tap Install.</div>
            </div>
          </div>
          <div style="display:flex;gap:14px;align-items:flex-start;">
            <div style="min-width:32px;height:32px;background:linear-gradient(135deg,#06D6A0,#028A5E);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;">✓</div>
            <div>
              <div style="font-weight:600;font-size:15px;margin-bottom:3px;color:var(--green);" data-i18n="apk.guide.step4.title">Done! Launch the App</div>
              <div style="color:var(--text-muted);font-size:13px;" data-i18n="apk.guide.step4.desc">You’ll see the RoxLudo icon on your home screen. Tap to start playing. 🎲</div>
            </div>
          </div>
        </div>
        <div style="margin-top:18px;padding:12px;background:rgba(255,215,0,0.06);border:1px solid rgba(255,215,0,0.2);border-radius:10px;font-size:13px;color:var(--text-muted);" data-i18n-html="apk.note">
          ⚠️ <strong style="color:var(--gold);">Note:</strong> Only APKs downloaded from roxludo.com are safe. Avoid third-party sources.
        </div>
      </div>
    </div>
  </div>
</div>

<!-- NAVBAR -->
<nav>
  <a class="nav-logo" href="/">
    <img src="{{ asset('logo.png') }}" alt="RoxLudo Logo" onerror="this.style.display='none'; document.getElementById('logoFallback').style.display='flex';">
    <div class="logo-fallback" id="logoFallback" style="display:none;">🎲</div>
    <span class="nav-brand">RoxLudo</span>
  </a>
  <ul class="nav-links">
    <li><a href="#tournaments" data-i18n="nav.tournaments">Tournaments</a></li>
    <li><a href="#features" data-i18n="nav.features">Features</a></li>
    <li><a href="#how-to-play" data-i18n="nav.how">How to Play</a></li>
    <li><a href="#" onclick="openModal('guideModal')" data-i18n="nav.guide">Guide</a></li>
    <li><a href="#" onclick="openModal('faqModal')" data-i18n="nav.faq">FAQ</a></li>
  </ul>
  <div style="display:flex;align-items:center;gap:10px;">
    <div class="lang-toggle" role="group" aria-label="Language">
      <button class="lang-btn active" type="button" data-lang="en">EN</button>
      <button class="lang-btn" type="button" data-lang="hi">HI</button>
    </div>
    <a href="/login" class="btn-login" data-i18n="nav.login">Login →</a>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="orb orb-1"></div>
  <div class="orb orb-2"></div>
  <div class="orb orb-3"></div>

  <div class="hero-content">
    <div class="hero-badge" data-i18n="hero.badge"><span>🏆</span> World's #1 Ludo Tournament Platform</div>
    <h1>
      <span class="h1-line1" data-i18n="hero.title1">Roll the Dice.</span>
      <span class="h1-line2" data-i18n="hero.title2">Rule the Arena.</span>
    </h1>
    <p class="hero-sub" data-i18n-html="hero.sub">
      Real-time multiplayer Ludo with <strong>live tournaments</strong>, massive prize pools, and your own private matches. Compete, win, dominate.
    </p>
    <div class="hero-cta">
      <a href="/login" class="btn-primary" data-i18n="hero.cta.start">🎲 Start Playing Free</a>
      <a href="#" class="btn-apk" onclick="openModal('apkModal')" data-i18n="hero.cta.apk">📱 Download APK</a>
      <a href="#" class="btn-secondary" onclick="openModal('guideModal')" data-i18n="hero.cta.guide">📖 How to Play</a>
      <a href="#" class="btn-secondary" onclick="openModal('faqModal')" data-i18n="hero.cta.faq">❓ FAQ</a>
    </div>

    <div class="board-visual">
      <div class="board-glow"></div>
      <div class="board-3d">
        <div class="board-grid">
          <div class="board-cell cell-green">♟</div>
          <div class="board-cell cell-path"></div>
          <div class="board-cell cell-blue">♟</div>
          <div class="board-cell cell-path"></div>
          <div class="board-cell cell-center">🎲</div>
          <div class="board-cell cell-path"></div>
          <div class="board-cell cell-red">♟</div>
          <div class="board-cell cell-path"></div>
          <div class="board-cell cell-yellow">♟</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- STATS -->
<div class="stats-strip">
  <div class="stats-inner">
    <div class="stat-item reveal">
      <div class="stat-num">2.4M+</div>
      <div class="stat-label" data-i18n="stats.active">Active Players</div>
    </div>
    <div class="stat-item reveal">
      <div class="stat-num">₹50L+</div>
      <div class="stat-label" data-i18n="stats.prize">Prize Given Out</div>
    </div>
    <div class="stat-item reveal">
      <div class="stat-num">12K+</div>
      <div class="stat-label" data-i18n="stats.tournaments">Tournaments Held</div>
    </div>
    <div class="stat-item reveal">
      <div class="stat-num">99.9%</div>
      <div class="stat-label" data-i18n="stats.uptime">Uptime Guarantee</div>
    </div>
  </div>
</div>

<!-- TOURNAMENTS -->
<section id="tournaments">
  <div class="section-inner">
    <div class="section-label" data-i18n="tournaments.label">🏆 Live & Upcoming</div>
    <h2 class="section-title" data-i18n-html="tournaments.title">Join a Tournament<br><span style="color: var(--gold);">Win Real Prizes</span></h2>
    <p class="section-desc" data-i18n="tournaments.desc">Public tournaments start every hour. Or host a private tournament with your friends.</p>

    <div class="tournaments-grid reveal" id="tournamentsGrid">
      <!-- Loaded from Laravel API: /api/homepage-cards -->
    </div>
  </div>
</section>

<!-- FEATURES -->
<section id="features" style="background: var(--bg-card);">
  <div class="section-inner">
    <div class="section-label" data-i18n="features.label">⚡ Platform Features</div>
    <h2 class="section-title" data-i18n-html="features.title">Why Choose<br><span style="color: var(--gold);">RoxLudo?</span></h2>
    <div class="features-grid reveal">
      <div class="feature-card">
        <div class="feature-icon fi-gold">🏆</div>
        <div class="feature-title" data-i18n="features.items.create.title">Create Your Tournament</div>
        <div class="feature-desc" data-i18n="features.items.create.desc">Create public or private tournaments with your own rules. Entry fee, prize pool, max players — you decide.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon fi-red">⚡</div>
        <div class="feature-title" data-i18n="features.items.realtime.title">Real-Time Multiplayer</div>
        <div class="feature-desc" data-i18n="features.items.realtime.desc">Zero lag gameplay. WebSocket tech delivers instant moves and live leaderboard updates.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon fi-blue">🔒</div>
        <div class="feature-title" data-i18n="features.items.private.title">Private Rooms</div>
        <div class="feature-desc" data-i18n="features.items.private.desc">Want to play only with friends? Create password-protected rooms and share an invite link.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon fi-green">💸</div>
        <div class="feature-title" data-i18n="features.items.withdraw.title">Instant Withdrawals</div>
        <div class="feature-desc" data-i18n="features.items.withdraw.desc">Win and withdraw directly to UPI or bank. 24-hour processing guaranteed.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon fi-gold">📊</div>
        <div class="feature-title" data-i18n="features.items.leaderboard.title">Live Leaderboard</div>
        <div class="feature-desc" data-i18n="features.items.leaderboard.desc">Real-time rankings. Track your progress, compare with others, and win season rewards.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon fi-red">🛡️</div>
        <div class="feature-title" data-i18n="features.items.anticheat.title">Anti-Cheat System</div>
        <div class="feature-desc" data-i18n="features.items.anticheat.desc">Fair play guaranteed. Advanced algorithms detect cheating. 100% random verified dice rolls.</div>
      </div>
    </div>
  </div>
</section>

<!-- HOW TO PLAY -->
<section class="howto-section" id="how-to-play">
  <div class="section-inner">
    <div class="section-label" data-i18n="how.label">🎮 Quick Start</div>
    <h2 class="section-title" data-i18n-html="how.title">Start Playing<br><span style="color: var(--gold);">4 Simple Steps</span></h2>
    <div class="steps-grid reveal">
      <div class="step">
        <div class="step-num">01</div>
        <div class="step-icon">👤</div>
        <div class="step-title" data-i18n="how.steps.register.title">Create an Account</div>
        <div class="step-desc" data-i18n="how.steps.register.desc">Sign up free, set up your profile, and claim your welcome bonus.</div>
      </div>
      <div class="step">
        <div class="step-num">02</div>
        <div class="step-icon">🔍</div>
        <div class="step-title" data-i18n="how.steps.find.title">Find a Tournament</div>
        <div class="step-desc" data-i18n="how.steps.find.desc">Browse tournaments that match your budget and skill level.</div>
      </div>
      <div class="step">
        <div class="step-num">03</div>
        <div class="step-icon">🎲</div>
        <div class="step-title" data-i18n="how.steps.play.title">Play and Win</div>
        <div class="step-desc" data-i18n="how.steps.play.desc">Use smart strategy, block opponents, save tokens, and finish first.</div>
      </div>
      <div class="step">
        <div class="step-num">04</div>
        <div class="step-icon">💰</div>
        <div class="step-title" data-i18n="how.steps.withdraw.title">Withdraw Instantly</div>
        <div class="step-desc" data-i18n="how.steps.withdraw.desc">Win prizes and withdraw directly to your UPI or bank.</div>
      </div>
    </div>
  </div>
</section>

<!-- GUIDE + FAQ CARDS -->
<section>
  <div class="section-inner">
    <div class="section-label" data-i18n="resources.label">📚 Resources</div>
    <h2 class="section-title" data-i18n-html="resources.title">Everything You Need<br><span style="color: var(--gold);">Right Here</span></h2>
    <div class="guide-faq reveal">
      <div class="guide-card" onclick="openModal('guideModal')" style="cursor:pointer;">
        <div class="gc-icon gold-bg">📖</div>
        <div class="gc-title" data-i18n="resources.guide.title">Complete Player Guide</div>
        <div class="gc-desc" data-i18n="resources.guide.desc">From basic rules to advanced tournament strategy — everything in one place.</div>
        <a class="btn-primary" style="margin-top:8px; padding: 12px 24px; font-size:15px;" data-i18n="resources.guide.cta">Read Full Guide →</a>
      </div>
      <div class="faq-card" onclick="openModal('faqModal')" style="cursor:pointer;">
        <div class="gc-icon blue-bg">❓</div>
        <div class="gc-title" data-i18n="resources.faq.title">Frequently Asked Questions</div>
        <div class="gc-desc" data-i18n="resources.faq.desc">Withdrawals, tournaments, rules, account — quick and clear answers.</div>
        <a class="btn-secondary" style="margin-top:8px; padding: 12px 24px; font-size:15px;" data-i18n="resources.faq.cta">View All FAQs →</a>
      </div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div class="footer-inner">
    <div class="footer-top">
      <div class="footer-brand">
        <div class="nav-brand" style="font-size:20px;">🎲 RoxLudo</div>
        <p data-i18n="footer.brand">India's most trusted real-money Ludo platform. Fair play, instant withdrawals, and epic tournaments.</p>
      </div>
      <div class="footer-col">
        <h4 data-i18n="footer.game.title">Game</h4>
        <a href="#tournaments" data-i18n="footer.game.tournaments">Tournaments</a>
        <a href="#" onclick="openModal('guideModal')" data-i18n="footer.game.how">How to Play</a>
        <a href="#" data-i18n="footer.game.leaderboard">Leaderboard</a>
        <a href="#" data-i18n="footer.game.practice">Practice Mode</a>
      </div>
      <div class="footer-col">
        <h4 data-i18n="footer.support.title">Support</h4>
        <a href="#" onclick="openModal('faqModal')" data-i18n="footer.support.faq">FAQ</a>
        <a href="#" data-i18n="footer.support.contact">Contact Us</a>
        <a href="#" data-i18n="footer.support.report">Report Issue</a>
        <a href="#" data-i18n="footer.support.community">Community</a>
      </div>
      <div class="footer-col">
        <h4 data-i18n="footer.legal.title">Legal</h4>
        <a href="/terms" data-i18n="footer.legal.terms">Terms of Service</a>
        <a href="/privacy" data-i18n="footer.legal.privacy">Privacy Policy</a>
        <a href="/fair-play" data-i18n="footer.legal.fair">Fair Play Policy</a>
        <a href="/responsible-gaming" data-i18n="footer.legal.responsible">Responsible Gaming</a>
      </div>
    </div>
    <div class="footer-bottom">
      <span data-i18n="footer.copy">© 2025 RoxLudo. All rights reserved.</span>
      <span data-i18n="footer.made">Made with ❤️ for Ludo lovers across India 🇮🇳</span>
    </div>
  </div>
</footer>

<script>
  // ── TOURNAMENT DATA — fetched from Laravel API ──
  const FALLBACK_TOURNAMENTS = {
    en: [
      { id:1, card_color:'gold',   status_badge:'live', status_text:'Live Now',          icon:'🔥', name:'Grand Sunday Classic', description:'128-player open tournament. Only 23 seats left — join fast.', meta1_label:'Prize Pool', meta1_value:'₹25,000', meta2_label:'Entry', meta2_value:'₹199',  meta3_label:'Players',    meta3_value:'105/128' },
      { id:2, card_color:'blue',   status_badge:'open', status_text:'Open Registration', icon:'⚡', name:'Rookie Rumble',        description:'A special tournament for new players. Account age under 2 months. Free entry.',  meta1_label:'Prize Pool', meta1_value:'₹5,000',  meta2_label:'Entry', meta2_value:'FREE',  meta3_label:'Players',    meta3_value:'56/64'   },
      { id:3, card_color:'purple', status_badge:'soon', status_text:'Starting Soon',     icon:'👑', name:'Champions League',     description:'Premium tournament for ranked players only. Top 10 rating required.',            meta1_label:'Prize Pool', meta1_value:'₹1,00,000',meta2_label:'Entry', meta2_value:'₹999', meta3_label:'Starts In',  meta3_value:'2h 14m'  }
    ],
    hi: [
      { id:1, card_color:'gold',   status_badge:'live', status_text:'लाइव',             icon:'🔥', name:'Grand Sunday Classic', description:'128‑प्लेयर ओपन टूर्नामेंट। सिर्फ 23 सीटें बची हैं — जल्दी जॉइन करें।', meta1_label:'प्राइज़ पूल', meta1_value:'₹25,000', meta2_label:'एंट्री', meta2_value:'₹199',  meta3_label:'प्लेयर',    meta3_value:'105/128' },
      { id:2, card_color:'blue',   status_badge:'open', status_text:'रजिस्ट्रेशन ओपन',  icon:'⚡', name:'Rookie Rumble',        description:'नए खिलाड़ियों के लिए स्पेशल टूर्नामेंट। अकाउंट उम्र 2 महीने से कम। फ्री एंट्री।',  meta1_label:'प्राइज़ पूल', meta1_value:'₹5,000',  meta2_label:'एंट्री', meta2_value:'FREE',  meta3_label:'प्लेयर',    meta3_value:'56/64'   },
      { id:3, card_color:'purple', status_badge:'soon', status_text:'जल्द शुरू',        icon:'👑', name:'Champions League',     description:'रैंक्ड खिलाड़ियों के लिए प्रीमियम टूर्नामेंट। टॉप 10 रेटिंग जरूरी।',            meta1_label:'प्राइज़ पूल', meta1_value:'₹1,00,000',meta2_label:'एंट्री', meta2_value:'₹999', meta3_label:'शुरू',  meta3_value:'2h 14m'  }
    ]
  };

  const I18N = {
    en: {
      'nav.tournaments': 'Tournaments',
      'nav.features': 'Features',
      'nav.how': 'How to Play',
      'nav.guide': 'Guide',
      'nav.faq': 'FAQ',
      'nav.login': 'Login →',
      'hero.badge': "🏆 World's #1 Ludo Tournament Platform",
      'hero.title1': 'Roll the Dice.',
      'hero.title2': 'Rule the Arena.',
      'hero.sub': 'Real-time multiplayer Ludo with <strong>live tournaments</strong>, massive prize pools, and your own private matches. Compete, win, dominate.',
      'hero.cta.start': '🎲 Start Playing Free',
      'hero.cta.apk': '📱 Download APK',
      'hero.cta.guide': '📖 How to Play',
      'hero.cta.faq': '❓ FAQ',
      'stats.active': 'Active Players',
      'stats.prize': 'Prize Given Out',
      'stats.tournaments': 'Tournaments Held',
      'stats.uptime': 'Uptime Guarantee',
      'tournaments.label': '🏆 Live & Upcoming',
      'tournaments.title': 'Join a Tournament<br><span style="color: var(--gold);">Win Real Prizes</span>',
      'tournaments.desc': 'Public tournaments start every hour. Or host a private tournament with your friends.',
      'features.label': '⚡ Platform Features',
      'features.title': 'Why Choose<br><span style="color: var(--gold);">RoxLudo?</span>',
      'features.items.create.title': 'Create Your Tournament',
      'features.items.create.desc': 'Create public or private tournaments with your own rules. Entry fee, prize pool, max players — you decide.',
      'features.items.realtime.title': 'Real-Time Multiplayer',
      'features.items.realtime.desc': 'Zero lag gameplay. WebSocket tech delivers instant moves and live leaderboard updates.',
      'features.items.private.title': 'Private Rooms',
      'features.items.private.desc': 'Want to play only with friends? Create password-protected rooms and share an invite link.',
      'features.items.withdraw.title': 'Instant Withdrawals',
      'features.items.withdraw.desc': 'Win and withdraw directly to UPI or bank. 24-hour processing guaranteed.',
      'features.items.leaderboard.title': 'Live Leaderboard',
      'features.items.leaderboard.desc': 'Real-time rankings. Track your progress, compare with others, and win season rewards.',
      'features.items.anticheat.title': 'Anti-Cheat System',
      'features.items.anticheat.desc': 'Fair play guaranteed. Advanced algorithms detect cheating. 100% random verified dice rolls.',
      'how.label': '🎮 Quick Start',
      'how.title': 'Start Playing<br><span style="color: var(--gold);">4 Simple Steps</span>',
      'how.steps.register.title': 'Create an Account',
      'how.steps.register.desc': 'Sign up free, set up your profile, and claim your welcome bonus.',
      'how.steps.find.title': 'Find a Tournament',
      'how.steps.find.desc': 'Browse tournaments that match your budget and skill level.',
      'how.steps.play.title': 'Play and Win',
      'how.steps.play.desc': 'Use smart strategy, block opponents, save tokens, and finish first.',
      'how.steps.withdraw.title': 'Withdraw Instantly',
      'how.steps.withdraw.desc': 'Win prizes and withdraw directly to your UPI or bank.',
      'resources.label': '📚 Resources',
      'resources.title': 'Everything You Need<br><span style="color: var(--gold);">Right Here</span>',
      'resources.guide.title': 'Complete Player Guide',
      'resources.guide.desc': 'From basic rules to advanced tournament strategy — everything in one place.',
      'resources.guide.cta': 'Read Full Guide →',
      'resources.faq.title': 'Frequently Asked Questions',
      'resources.faq.desc': 'Withdrawals, tournaments, rules, account — quick and clear answers.',
      'resources.faq.cta': 'View All FAQs →',
      'footer.brand': "India's most trusted real-money Ludo platform. Fair play, instant withdrawals, and epic tournaments.",
      'footer.game.title': 'Game',
      'footer.game.tournaments': 'Tournaments',
      'footer.game.how': 'How to Play',
      'footer.game.leaderboard': 'Leaderboard',
      'footer.game.practice': 'Practice Mode',
      'footer.support.title': 'Support',
      'footer.support.faq': 'FAQ',
      'footer.support.contact': 'Contact Us',
      'footer.support.report': 'Report Issue',
      'footer.support.community': 'Community',
      'footer.legal.title': 'Legal',
      'footer.legal.terms': 'Terms of Service',
      'footer.legal.privacy': 'Privacy Policy',
      'footer.legal.fair': 'Fair Play Policy',
      'footer.legal.responsible': 'Responsible Gaming',
      'footer.copy': '© 2025 RoxLudo. All rights reserved.',
      'footer.made': 'Made with ❤️ for Ludo lovers across India 🇮🇳',
      'guide.title': '📖 How to Play — Complete Guide',
      'guide.basic.title': '🎲 Basic Rules',
      'guide.basic.desc': 'Ludo is a classic board game with 2–4 players. Each player must move all 4 tokens from base to home.',
      'guide.start.title': '🎯 Game Start',
      'guide.start.items.1': 'Roll a 6 to bring a token onto the board.',
      'guide.start.items.2': 'Move your token based on the dice roll.',
      'guide.start.items.3': 'Capture opponent tokens to send them back to base.',
      'guide.start.items.4': 'Safe squares protect tokens from capture.',
      'guide.tournament.title': '🏆 Tournament Rules',
      'guide.tournament.items.1': 'Anyone can join public tournaments.',
      'guide.tournament.items.2': 'Private tournaments are invite-only.',
      'guide.tournament.items.3': 'Winners receive the prize pool.',
      'guide.tournament.items.4': 'Fair play rules are strictly enforced.',
      'guide.create.title': '🎪 Create Tournament',
      'guide.create.desc': 'Go to the dashboard, click “Create Tournament,” choose Public/Private, set entry fee and prizes, then share the link.',
      'guide.prize.title': '💰 Prize Pool',
      'guide.prize.items.1': 'Winner receives 70% of the prize pool.',
      'guide.prize.items.2': 'Runner-up receives 20%.',
      'guide.prize.items.3': 'Platform fee: 10%.',
      'guide.note': 'For a full guide, see docs/ludo-user-guide-hinglish.html.',
      'faq.title': '❓ Frequently Asked Questions',
      'faq.q1': 'Is the game free to play?',
      'faq.a1': 'Yes. The basic game is free with practice rooms and free tournaments. Prize tournaments have an entry fee that goes into the prize pool.',
      'faq.q2': 'How do I create a private tournament?',
      'faq.a2': 'Dashboard → Create Tournament → select Private → set password/invite link → share with friends. Only invited players can join.',
      'faq.q3': 'How do I withdraw winnings?',
      'faq.a3': 'Profile → Wallet → Withdraw → add UPI/Bank details. Funds transfer in 24–48 hours. Minimum withdrawal is ₹50.',
      'faq.q4': 'Is cheating possible in the game?',
      'faq.a4': 'No. Our anti-cheat system runs 24/7. Dice rolls are verified random. Suspicious activity can lead to a ban.',
      'faq.q5': 'How many players can play together?',
      'faq.a5': 'Classic Ludo supports 2–4 players. Tournaments can have 8 to 512 players in bracket format. Team mode is also available.',
      'faq.q6': 'Does it work on mobile?',
      'faq.a6': 'Yes. The site is fully responsive on Android and iOS. A dedicated app is coming soon.',
      'apk.title': '📱 Download RoxLudo APK',
      'apk.appTitle': 'RoxLudo Android App',
      'apk.meta': 'Version 1.0.0 • ~25 MB • Android 6.0+',
      'apk.cta': '⬇️ Download APK',
      'apk.guide.title': '📋 Android Installation Guide',
      'apk.guide.step1.title': 'Download the APK',
      'apk.guide.step1.desc': 'Tap “Download APK.” The file will be saved to your Downloads folder.',
      'apk.guide.step2.title': 'Allow Unknown Sources',
      'apk.guide.step2.desc': 'Settings → Security → Install Unknown Apps → allow Browser/Files (one time).',
      'apk.guide.step3.title': 'Open the APK File',
      'apk.guide.step3.desc': 'Go to Downloads → tap roxludo.apk → tap Install.',
      'apk.guide.step4.title': 'Done! Launch the App',
      'apk.guide.step4.desc': 'You’ll see the RoxLudo icon on your home screen. Tap to start playing. 🎲',
      'apk.note': '⚠️ <strong style="color: var(--gold);">Note:</strong> Only APKs downloaded from roxludo.com are safe. Avoid third-party sources.'
    },
    hi: {
      'nav.tournaments': 'टूर्नामेंट',
      'nav.features': 'फीचर्स',
      'nav.how': 'कैसे खेलें',
      'nav.guide': 'गाइड',
      'nav.faq': 'FAQs',
      'nav.login': 'लॉगिन →',
      'hero.badge': '🏆 दुनिया का #1 लूडो टूर्नामेंट प्लेटफ़ॉर्म',
      'hero.title1': 'डाइस रोल करें।',
      'hero.title2': 'अरेना पर राज करें।',
      'hero.sub': 'रियल‑टाइम मल्टीप्लेयर लूडो के साथ <strong>लाइव टूर्नामेंट</strong>, बड़े प्राइज़ पूल और प्राइवेट मैच। खेलें, जीतें, राज करें।',
      'hero.cta.start': '🎲 फ्री में खेलें',
      'hero.cta.apk': '📱 APK डाउनलोड करें',
      'hero.cta.guide': '📖 कैसे खेलें',
      'hero.cta.faq': '❓ FAQs',
      'stats.active': 'सक्रिय खिलाड़ी',
      'stats.prize': 'कुल इनाम',
      'stats.tournaments': 'आयोजित टूर्नामेंट',
      'stats.uptime': 'अपटाइम गारंटी',
      'tournaments.label': '🏆 लाइव और आने वाले',
      'tournaments.title': 'टूर्नामेंट जॉइन करें<br><span style="color: var(--gold);">रियल प्राइज़ जीतें</span>',
      'tournaments.desc': 'पब्लिक टूर्नामेंट हर घंटे शुरू होते हैं, या दोस्तों के साथ प्राइवेट टूर्नामेंट बनाएं।',
      'features.label': '⚡ प्लेटफ़ॉर्म फीचर्स',
      'features.title': 'क्यों चुनें<br><span style="color: var(--gold);">RoxLudo?</span>',
      'features.items.create.title': 'अपना टूर्नामेंट बनाएं',
      'features.items.create.desc': 'पब्लिक या प्राइवेट टूर्नामेंट अपनी नियमों के साथ बनाएं। एंट्री फीस, प्राइज़ पूल, मैक्स प्लेयर आप तय करें।',
      'features.items.realtime.title': 'रियल‑टाइम मल्टीप्लेयर',
      'features.items.realtime.desc': 'लग‑फ्री गेमप्ले। WebSocket से इंस्टेंट मूव्स और लाइव लीडरबोर्ड अपडेट्स।',
      'features.items.private.title': 'प्राइवेट रूम्स',
      'features.items.private.desc': 'केवल दोस्तों के साथ खेलना है? पासवर्ड‑प्रोटेक्टेड रूम बनाएं और लिंक शेयर करें।',
      'features.items.withdraw.title': 'इंस्टेंट विदड्रॉ',
      'features.items.withdraw.desc': 'जीतें और सीधे UPI या बैंक में निकालें। 24‑घंटे प्रोसेसिंग गारंटी।',
      'features.items.leaderboard.title': 'लाइव लीडरबोर्ड',
      'features.items.leaderboard.desc': 'रियल‑टाइम रैंकिंग। अपनी प्रोग्रेस ट्रैक करें और सीज़न रिवॉर्ड्स जीतें।',
      'features.items.anticheat.title': 'एंटी‑चीट सिस्टम',
      'features.items.anticheat.desc': 'फेयर प्ले सुनिश्चित। एडवांस्ड एल्गोरिदम चीटिंग पकड़ते हैं। 100% रैंडम वेरिफ़ाइड डाइस रोल्स।',
      'how.label': '🎮 क्विक स्टार्ट',
      'how.title': 'खेलना शुरू करें<br><span style="color: var(--gold);">4 आसान स्टेप्स</span>',
      'how.steps.register.title': 'अकाउंट बनाएं',
      'how.steps.register.desc': 'फ्री साइन‑अप करें, प्रोफाइल सेट करें और वेलकम बोनस लें।',
      'how.steps.find.title': 'टूर्नामेंट चुनें',
      'how.steps.find.desc': 'अपने बजट और स्किल के हिसाब से टूर्नामेंट चुनें।',
      'how.steps.play.title': 'खेलें और जीतें',
      'how.steps.play.desc': 'स्मार्ट स्ट्रैटेजी अपनाएं, विरोधियों को ब्लॉक करें और पहले खत्म करें।',
      'how.steps.withdraw.title': 'तुरंत विदड्रॉ',
      'how.steps.withdraw.desc': 'इनाम जीतें और सीधे UPI/बैंक में निकालें।',
      'resources.label': '📚 रिसोर्सेस',
      'resources.title': 'हर ज़रूरत<br><span style="color: var(--gold);">यहीं मिलेगी</span>',
      'resources.guide.title': 'कम्प्लीट प्लेयर गाइड',
      'resources.guide.desc': 'बेसिक रूल्स से लेकर एडवांस्ड टूर्नामेंट स्ट्रैटेजी तक सब कुछ।',
      'resources.guide.cta': 'पूरा गाइड पढ़ें →',
      'resources.faq.title': 'अक्सर पूछे जाने वाले सवाल',
      'resources.faq.desc': 'विदड्रॉ, टूर्नामेंट, नियम, अकाउंट — साफ और जल्दी जवाब।',
      'resources.faq.cta': 'सभी FAQs देखें →',
      'footer.brand': 'भारत का सबसे भरोसेमंद रियल‑मनी लूडो प्लेटफ़ॉर्म। फेयर प्ले, इंस्टेंट विदड्रॉ, और शानदार टूर्नामेंट।',
      'footer.game.title': 'गेम',
      'footer.game.tournaments': 'टूर्नामेंट',
      'footer.game.how': 'कैसे खेलें',
      'footer.game.leaderboard': 'लीडरबोर्ड',
      'footer.game.practice': 'प्रैक्टिस मोड',
      'footer.support.title': 'सपोर्ट',
      'footer.support.faq': 'FAQs',
      'footer.support.contact': 'कॉन्टैक्ट करें',
      'footer.support.report': 'इश्यू रिपोर्ट करें',
      'footer.support.community': 'कम्युनिटी',
      'footer.legal.title': 'लीगल',
      'footer.legal.terms': 'सेवा की शर्तें',
      'footer.legal.privacy': 'प्राइवेसी पॉलिसी',
      'footer.legal.fair': 'फेयर प्ले पॉलिसी',
      'footer.legal.responsible': 'जिम्मेदार गेमिंग',
      'footer.copy': '© 2025 RoxLudo. सभी अधिकार सुरक्षित।',
      'footer.made': 'भारत के लूडो लवर्स के लिए ❤️ के साथ बनाया गया',
      'guide.title': '📖 कैसे खेलें — कम्प्लीट गाइड',
      'guide.basic.title': '🎲 बेसिक रूल्स',
      'guide.basic.desc': 'लूडो एक क्लासिक बोर्ड गेम है जिसमें 2–4 खिलाड़ी होते हैं। सभी 4 टोकन को बेस से होम तक ले जाना होता है।',
      'guide.start.title': '🎯 गेम स्टार्ट',
      'guide.start.items.1': '6 आने पर टोकन बोर्ड पर आता है।',
      'guide.start.items.2': 'डाइस रोल के अनुसार टोकन मूव करें।',
      'guide.start.items.3': 'प्रतिद्वंदी का टोकन कैप्चर करके उसे बेस पर भेजें।',
      'guide.start.items.4': 'सेफ स्क्वेयर पर टोकन सुरक्षित रहता है।',
      'guide.tournament.title': '🏆 टूर्नामेंट नियम',
      'guide.tournament.items.1': 'पब्लिक टूर्नामेंट में कोई भी जॉइन कर सकता है।',
      'guide.tournament.items.2': 'प्राइवेट टूर्नामेंट केवल इनवाइट से जुड़ते हैं।',
      'guide.tournament.items.3': 'विनर को प्राइज़ पूल मिलता है।',
      'guide.tournament.items.4': 'फेयर प्ले रूल्स सख्ती से लागू हैं।',
      'guide.create.title': '🎪 टूर्नामेंट बनाएं',
      'guide.create.desc': 'डैशबोर्ड में “Create Tournament” पर जाएं, Public/Private चुनें, एंट्री फीस व प्राइज़ सेट करें और लिंक शेयर करें।',
      'guide.prize.title': '💰 प्राइज़ पूल',
      'guide.prize.items.1': 'विनर को 70% प्राइज़ पूल मिलता है।',
      'guide.prize.items.2': 'रनर‑अप को 20% मिलता है।',
      'guide.prize.items.3': 'प्लेटफ़ॉर्म फीस: 10%।',
      'guide.note': 'पूरा गाइड देखने के लिए docs/ludo-user-guide-hinglish.html देखें।',
      'faq.title': '❓ अक्सर पूछे जाने वाले सवाल',
      'faq.q1': 'क्या गेम फ्री है?',
      'faq.a1': 'हाँ। बेसिक गेम फ्री है, प्रैक्टिस रूम्स और फ्री टूर्नामेंट उपलब्ध हैं। प्राइज़ टूर्नामेंट में एंट्री फीस लगती है।',
      'faq.q2': 'प्राइवेट टूर्नामेंट कैसे बनाएं?',
      'faq.a2': 'Dashboard → Create Tournament → Private चुनें → पासवर्ड/इनवाइट लिंक सेट करें → दोस्तों से शेयर करें।',
      'faq.q3': 'विनिंग अमाउंट कैसे निकालें?',
      'faq.a3': 'Profile → Wallet → Withdraw → UPI/Bank डिटेल्स जोड़ें। 24–48 घंटे में ट्रांसफर हो जाता है। न्यूनतम विदड्रॉ ₹50।',
      'faq.q4': 'क्या गेम में चीटिंग होती है?',
      'faq.a4': 'नहीं। हमारा एंटी‑चीट सिस्टम 24/7 एक्टिव है। डाइस रोल्स वेरिफ़ाइड रैंडम होते हैं।',
      'faq.q5': 'एक साथ कितने खिलाड़ी खेल सकते हैं?',
      'faq.a5': 'क्लासिक लूडो में 2–4 खिलाड़ी। टूर्नामेंट में 8 से 512 खिलाड़ी ब्रैकेट फॉर्मेट में। टीम मोड भी उपलब्ध है।',
      'faq.q6': 'क्या मोबाइल पर चलता है?',
      'faq.a6': 'हाँ। वेबसाइट Android/iOS दोनों पर सही चलती है। डेडिकेटेड ऐप जल्द आ रही है।',
      'apk.title': '📱 RoxLudo APK डाउनलोड करें',
      'apk.appTitle': 'RoxLudo Android App',
      'apk.meta': 'Version 1.0.0 • ~25 MB • Android 6.0+',
      'apk.cta': '⬇️ APK डाउनलोड करें',
      'apk.guide.title': '📋 Android इंस्टॉलेशन गाइड',
      'apk.guide.step1.title': 'APK डाउनलोड करें',
      'apk.guide.step1.desc': '“Download APK” पर टैप करें। फ़ाइल Downloads में सेव होगी।',
      'apk.guide.step2.title': 'Unknown Sources Allow करें',
      'apk.guide.step2.desc': 'Settings → Security → Install Unknown Apps → Browser/Files को allow करें (एक बार)।',
      'apk.guide.step3.title': 'APK फ़ाइल खोलें',
      'apk.guide.step3.desc': 'Downloads में जाएं → roxludo.apk पर टैप करें → Install दबाएं।',
      'apk.guide.step4.title': 'Done! ऐप लॉन्च करें',
      'apk.guide.step4.desc': 'होम स्क्रीन पर RoxLudo आइकन दिखेगा। टैप करके खेलना शुरू करें। 🎲',
      'apk.note': '⚠️ <strong style="color: var(--gold);">नोट:</strong> केवल roxludo.com से डाउनलोड की गई APK सुरक्षित है। थर्ड‑पार्टी सोर्स से डाउनलोड न करें।'
    }
  };

  let currentLang = 'en';

  function applyI18n(lang) {
    const pack = I18N[lang] || I18N.en;
    document.documentElement.setAttribute('lang', lang);
    currentLang = lang;

    document.querySelectorAll('[data-i18n]').forEach(el => {
      const key = el.getAttribute('data-i18n');
      if (pack[key]) el.textContent = pack[key];
    });

    document.querySelectorAll('[data-i18n-html]').forEach(el => {
      const key = el.getAttribute('data-i18n-html');
      if (pack[key]) el.innerHTML = pack[key];
    });

    document.querySelectorAll('.lang-btn').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.lang === lang);
    });

    localStorage.setItem('roxludo_lang', lang);
    if (window.__roxludoTournamentsLoaded) {
      renderTournaments(window.__roxludoTournamentsLoaded);
    }
  }

  async function loadTournaments() {
    try {
      const res = await fetch('/api/homepage-cards');
      if (!res.ok) throw new Error('API error');
      const data = await res.json();
      if (data.length) {
        window.__roxludoTournamentsLoaded = data;
        renderTournaments(data);
      } else {
        const fallback = FALLBACK_TOURNAMENTS[currentLang] || FALLBACK_TOURNAMENTS.en;
        window.__roxludoTournamentsLoaded = fallback;
        renderTournaments(fallback);
      }
    } catch(e) {
      const fallback = FALLBACK_TOURNAMENTS[currentLang] || FALLBACK_TOURNAMENTS.en;
      window.__roxludoTournamentsLoaded = fallback;
      renderTournaments(fallback);
    }
  }

  function renderTournaments(data) {
    const grid = document.getElementById('tournamentsGrid');
    if (!grid) return;
    grid.innerHTML = data.map(t => {
      const badge = t.status_badge === 'live'
        ? `<div class="t-badge live"><div class="live-dot"></div> ${t.status_text}</div>`
        : t.status_badge === 'open'
        ? `<div class="t-badge open">✓ ${t.status_text}</div>`
        : `<div class="t-badge soon">🕐 ${t.status_text}</div>`;
      return `
        <div class="t-card ${t.card_color}">
          ${badge}
          <div class="t-name">${t.icon} ${t.name}</div>
          <div class="t-desc">${t.description || ''}</div>
          <div class="t-meta">
            <div class="t-meta-item"><span class="t-meta-label">${t.meta1_label}</span><span class="t-meta-val">${t.meta1_value}</span></div>
            <div class="t-meta-item"><span class="t-meta-label">${t.meta2_label}</span><span class="t-meta-val">${t.meta2_value}</span></div>
            <div class="t-meta-item"><span class="t-meta-label">${t.meta3_label}</span><span class="t-meta-val">${t.meta3_value}</span></div>
          </div>
        </div>`;
    }).join('');
  }

  // Cursor glow effect
  const glow = document.getElementById('cursorGlow');
  document.addEventListener('mousemove', e => {
    glow.style.left = e.clientX + 'px';
    glow.style.top = e.clientY + 'px';
  });

  // Modals
  function openModal(id) {
    document.getElementById(id).classList.add('show');
    document.body.style.overflow = 'hidden';
  }
  function closeModal(id) {
    document.getElementById(id).classList.remove('show');
    document.body.style.overflow = '';
  }
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
      if (e.target === overlay) closeModal(overlay.id);
    });
  });
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.show').forEach(m => closeModal(m.id));
    }
  });

  // FAQ in main page
  document.querySelectorAll('.faq-q').forEach(q => {
    q.addEventListener('click', () => {
      const item = q.closest('.faq-item');
      const isOpen = item.classList.contains('open');
      document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));
      if (!isOpen) item.classList.add('open');
    });
  });

  // FAQ in modal
  function toggleFaqModal(q) {
    const item = q.closest('.faq-item');
    item.classList.toggle('open');
  }

  // Language toggle
  const initialLang = localStorage.getItem('roxludo_lang') || 'en';
  applyI18n(initialLang);
  document.querySelectorAll('.lang-btn').forEach(btn => {
    btn.addEventListener('click', () => applyI18n(btn.dataset.lang));
  });

  // Scroll reveal
  const revealEls = document.querySelectorAll('.reveal');
  const observer = new IntersectionObserver(entries => {
    entries.forEach((entry, i) => {
      if (entry.isIntersecting) {
        setTimeout(() => entry.target.classList.add('visible'), i * 100);
      }
    });
  }, { threshold: 0.1 });
  revealEls.forEach(el => observer.observe(el));

  // Animate stats on scroll
  const statNums = document.querySelectorAll('.stat-num');
  const statsObserver = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.animation = 'none';
        entry.target.offsetHeight;
        entry.target.style.animation = 'fadeInUp 0.6s ease forwards';
      }
    });
  }, { threshold: 0.5 });
  statNums.forEach(el => statsObserver.observe(el));

  // Load tournaments
  loadTournaments();
</script>
</body>
</html>
