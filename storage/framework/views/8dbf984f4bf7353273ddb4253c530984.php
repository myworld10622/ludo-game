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
      <div class="modal-title">📖 How to Play — Complete Guide</div>
      <button class="modal-close" onclick="closeModal('guideModal')">✕</button>
    </div>
    <div class="modal-body" id="guideContent">
      <h3>🎲 Basic Rules</h3>
      <p>Ludo ek classic board game hai jisme 2–4 players participate karte hain. Har player ko apne 4 tokens ko starting base se ghar tak pahunchana hota hai.</p>
      <h3>🎯 Game Start</h3>
      <ul>
        <li>6 aane par token board par aata hai</li>
        <li>Dice roll se apni token move karo</li>
        <li>Dusre ki token kill karke usse wapas base par bhejo</li>
        <li>Safe squares par dusra player kill nahi kar sakta</li>
      </ul>
      <h3>🏆 Tournament Rules</h3>
      <ul>
        <li>Public tournaments mein koi bhi join kar sakta hai</li>
        <li>Private tournaments sirf invite se join hoti hain</li>
        <li>Winner ko prize pool milta hai</li>
        <li>Fair play rules strictly follow karna zaroori hai</li>
      </ul>
      <h3>🎪 Create Tournament</h3>
      <p>Apna tournament create karne ke liye dashboard mein jao, "Create Tournament" click karo, type choose karo (Public/Private), entry fee aur prize set karo, aur share karo!</p>
      <h3>💰 Prize Pool</h3>
      <ul>
        <li>Winner ko 70% prize pool milta hai</li>
        <li>Runner-up ko 20% milta hai</li>
        <li>Platform fee: 10%</li>
      </ul>
      <p style="color: var(--gold); margin-top: 16px; font-weight: 600;">Full guide ke liye docs/ludo-user-guide-hinglish.html dekhein.</p>
    </div>
  </div>
</div>

<div class="modal-overlay" id="faqModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">❓ Frequently Asked Questions</div>
      <button class="modal-close" onclick="closeModal('faqModal')">✕</button>
    </div>
    <div class="modal-body">
      <div class="faq-list">
        <div class="faq-item">
          <div class="faq-q" onclick="toggleFaqModal(this)"><span>Kya ye game free mein khel sakte hain?</span><div class="faq-icon">+</div></div>
          <div class="faq-a">Haan! Basic game bilkul free hai. Practice rooms aur free tournaments available hain. Prize tournaments mein entry fee lagti hai jo prize pool mein jaati hai.</div>
        </div>
        <div class="faq-item">
          <div class="faq-q" onclick="toggleFaqModal(this)"><span>Private tournament kaise banate hain?</span><div class="faq-icon">+</div></div>
          <div class="faq-a">Dashboard → Create Tournament → Private select karo → Password/invite link set karo → Share karo friends ke saath. Sirf invited players hi join kar sakte hain.</div>
        </div>
        <div class="faq-item">
          <div class="faq-q" onclick="toggleFaqModal(this)"><span>Winning amount kaise withdraw karein?</span><div class="faq-icon">+</div></div>
          <div class="faq-a">Profile → Wallet → Withdraw → UPI/Bank details dalo. Amount 24–48 ghante mein transfer ho jaata hai. Minimum withdrawal ₹50 hai.</div>
        </div>
        <div class="faq-item">
          <div class="faq-q" onclick="toggleFaqModal(this)"><span>Game mein cheating hoti hai kya?</span><div class="faq-icon">+</div></div>
          <div class="faq-a">Bilkul nahi. Humara anti-cheat system 24/7 active hai. Fair dice algorithm se random numbers generate hote hain. Kisi bhi suspicious activity par account ban ho sakta hai.</div>
        </div>
        <div class="faq-item">
          <div class="faq-q" onclick="toggleFaqModal(this)"><span>Kitne players ek saath khel sakte hain?</span><div class="faq-icon">+</div></div>
          <div class="faq-a">Classic Ludo mein 2–4 players. Tournaments mein 8 se 512 players tak participate kar sakte hain bracket format mein. Team mode bhi available hai.</div>
        </div>
        <div class="faq-item">
          <div class="faq-q" onclick="toggleFaqModal(this)"><span>Mobile par kaam karta hai?</span><div class="faq-icon">+</div></div>
          <div class="faq-a">Haan! Website fully responsive hai aur Android/iOS dono par perfectly kaam karti hai. Dedicated app bhi coming soon hai.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- APK DOWNLOAD MODAL -->
<div class="modal-overlay" id="apkModal">
  <div class="modal" style="max-width:520px;">
    <div class="modal-header">
      <div class="modal-title">📱 Download RoxLudo APK</div>
      <button class="modal-close" onclick="closeModal('apkModal')">✕</button>
    </div>
    <div class="modal-body">
      <div style="text-align:center; padding: 10px 0 24px;">
        <div style="width:80px;height:80px;background:linear-gradient(135deg,#06D6A0,#028A5E);border-radius:20px;display:flex;align-items:center;justify-content:center;font-size:40px;margin:0 auto 16px;">📱</div>
        <h3 style="font-family:'Rajdhani',sans-serif;font-size:22px;font-weight:700;color:var(--text);margin-bottom:8px;">RoxLudo Android App</h3>
        <p style="color:var(--text-muted);font-size:14px;">Version 1.0.0 &nbsp;•&nbsp; ~25 MB &nbsp;•&nbsp; Android 6.0+</p>
      </div>
      <a href="<?php echo e($apkUrl); ?>" download style="display:flex;align-items:center;justify-content:center;gap:12px;background:linear-gradient(135deg,#06D6A0,#028A5E);color:#fff;font-family:'Rajdhani',sans-serif;font-size:18px;font-weight:700;padding:16px;border-radius:14px;text-decoration:none;letter-spacing:1px;text-transform:uppercase;box-shadow:0 8px 24px rgba(6,214,160,0.3);transition:all 0.2s;margin-bottom:28px;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">⬇️ Download APK</a>

      <div style="background:var(--bg-card2);border:1px solid var(--border);border-radius:16px;padding:24px;">
        <h3 style="font-family:'Rajdhani',sans-serif;font-size:18px;font-weight:700;color:var(--gold);margin-bottom:16px;">📋 Android Installation Guide</h3>
        <div style="display:flex;flex-direction:column;gap:14px;">
          <div style="display:flex;gap:14px;align-items:flex-start;">
            <div style="min-width:32px;height:32px;background:linear-gradient(135deg,var(--gold),#FF9500);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:#000;">1</div>
            <div>
              <div style="font-weight:600;font-size:15px;margin-bottom:3px;">APK Download Karo</div>
              <div style="color:var(--text-muted);font-size:13px;">Upar "Download APK" button dabao. File tumhare Downloads folder mein save hogi.</div>
            </div>
          </div>
          <div style="display:flex;gap:14px;align-items:flex-start;">
            <div style="min-width:32px;height:32px;background:linear-gradient(135deg,var(--gold),#FF9500);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:#000;">2</div>
            <div>
              <div style="font-weight:600;font-size:15px;margin-bottom:3px;">Unknown Sources Allow Karo</div>
              <div style="color:var(--text-muted);font-size:13px;">Settings → Security → "Install Unknown Apps" → Browser/Files ko allow karo. (Ek baar hi karna hai)</div>
            </div>
          </div>
          <div style="display:flex;gap:14px;align-items:flex-start;">
            <div style="min-width:32px;height:32px;background:linear-gradient(135deg,var(--gold),#FF9500);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:#000;">3</div>
            <div>
              <div style="font-weight:600;font-size:15px;margin-bottom:3px;">APK File Open Karo</div>
              <div style="color:var(--text-muted);font-size:13px;">Downloads folder mein jao → roxludo.apk tap karo → "Install" button dabao.</div>
            </div>
          </div>
          <div style="display:flex;gap:14px;align-items:flex-start;">
            <div style="min-width:32px;height:32px;background:linear-gradient(135deg,#06D6A0,#028A5E);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;">✓</div>
            <div>
              <div style="font-weight:600;font-size:15px;margin-bottom:3px;color:var(--green);">Done! App Launch Karo</div>
              <div style="color:var(--text-muted);font-size:13px;">Home screen par RoxLudo icon dikhega. Tap karo aur khelna shuru karo! 🎲</div>
            </div>
          </div>
        </div>
        <div style="margin-top:18px;padding:12px;background:rgba(255,215,0,0.06);border:1px solid rgba(255,215,0,0.2);border-radius:10px;font-size:13px;color:var(--text-muted);">
          ⚠️ <strong style="color:var(--gold);">Note:</strong> Sirf roxludo.com se download ki gayi APK safe hai. Third-party sources se download mat karo.
        </div>
      </div>
    </div>
  </div>
</div>

<!-- NAVBAR -->
<nav>
  <a class="nav-logo" href="/">
    <img src="<?php echo e(asset('logo.png')); ?>" alt="RoxLudo Logo" onerror="this.style.display='none'; document.getElementById('logoFallback').style.display='flex';">
    <div class="logo-fallback" id="logoFallback" style="display:none;">🎲</div>
    <span class="nav-brand">RoxLudo</span>
  </a>
  <ul class="nav-links">
    <li><a href="#tournaments">Tournaments</a></li>
    <li><a href="#features">Features</a></li>
    <li><a href="#how-to-play">How to Play</a></li>
    <li><a href="#" onclick="openModal('guideModal')">Guide</a></li>
    <li><a href="#" onclick="openModal('faqModal')">FAQ</a></li>
  </ul>
  <a href="/login" class="btn-login">Login →</a>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="orb orb-1"></div>
  <div class="orb orb-2"></div>
  <div class="orb orb-3"></div>

  <div class="hero-content">
    <div class="hero-badge"><span>🏆</span> World's #1 Ludo Tournament Platform</div>
    <h1>
      <span class="h1-line1">Roll the Dice.</span>
      <span class="h1-line2">Rule the Arena.</span>
    </h1>
    <p class="hero-sub">
      Real-time multiplayer Ludo with <strong>live tournaments</strong>, massive prize pools, and your own private matches. Compete, win, dominate.
    </p>
    <div class="hero-cta">
      <a href="/login" class="btn-primary">🎲 Start Playing Free</a>
      <a href="#" class="btn-apk" onclick="openModal('apkModal')">📱 Download APK</a>
      <a href="#" class="btn-secondary" onclick="openModal('guideModal')">📖 How to Play</a>
      <a href="#" class="btn-secondary" onclick="openModal('faqModal')">❓ FAQ</a>
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
      <div class="stat-label">Active Players</div>
    </div>
    <div class="stat-item reveal">
      <div class="stat-num">₹50L+</div>
      <div class="stat-label">Prize Given Out</div>
    </div>
    <div class="stat-item reveal">
      <div class="stat-num">12K+</div>
      <div class="stat-label">Tournaments Held</div>
    </div>
    <div class="stat-item reveal">
      <div class="stat-num">99.9%</div>
      <div class="stat-label">Uptime Guarantee</div>
    </div>
  </div>
</div>

<!-- TOURNAMENTS -->
<section id="tournaments">
  <div class="section-inner">
    <div class="section-label">🏆 Live & Upcoming</div>
    <h2 class="section-title">Join a Tournament<br><span style="color: var(--gold);">Win Real Prizes</span></h2>
    <p class="section-desc">Public tournaments har ghante start hote hain. Ya apna private tournament banao apne dosto ke liye.</p>

    <div class="tournaments-grid reveal" id="tournamentsGrid">
      <!-- Loaded from Laravel API: /api/homepage-cards -->
    </div>
  </div>
</section>

<!-- FEATURES -->
<section id="features" style="background: var(--bg-card);">
  <div class="section-inner">
    <div class="section-label">⚡ Platform Features</div>
    <h2 class="section-title">Kyun Choose Karein<br><span style="color: var(--gold);">RoxLudo?</span></h2>
    <div class="features-grid reveal">
      <div class="feature-card">
        <div class="feature-icon fi-gold">🏆</div>
        <div class="feature-title">Create Your Tournament</div>
        <div class="feature-desc">Public ya private tournament create karo apni rules ke saath. Entry fee, prize pool, max players — sab tum decide karo.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon fi-red">⚡</div>
        <div class="feature-title">Real-Time Multiplayer</div>
        <div class="feature-desc">Zero lag gameplay. WebSocket technology se instantaneous moves aur live leaderboard updates milte hain.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon fi-blue">🔒</div>
        <div class="feature-title">Private Rooms</div>
        <div class="feature-desc">Sirf dosto ke saath khelna hai? Password-protected private rooms banao. Invite link share karo bas.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon fi-green">💸</div>
        <div class="feature-title">Instant Withdrawals</div>
        <div class="feature-desc">Jeeto aur seedha UPI ya bank account mein withdraw karo. 24-hour processing guaranteed.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon fi-gold">📊</div>
        <div class="feature-title">Live Leaderboard</div>
        <div class="feature-desc">Real-time ranking system. Apni progress track karo, dusron se compare karo, aur season rewards jeeto.</div>
      </div>
      <div class="feature-card">
        <div class="feature-icon fi-red">🛡️</div>
        <div class="feature-title">Anti-Cheat System</div>
        <div class="feature-desc">Fair play guaranteed. Advanced algorithm se cheating detect hoti hai. 100% random aur verified dice rolls.</div>
      </div>
    </div>
  </div>
</section>

<!-- HOW TO PLAY -->
<section class="howto-section" id="how-to-play">
  <div class="section-inner">
    <div class="section-label">🎮 Quick Start</div>
    <h2 class="section-title">Khelna Shuru Karo<br><span style="color: var(--gold);">4 Simple Steps</span></h2>
    <div class="steps-grid reveal">
      <div class="step">
        <div class="step-num">01</div>
        <div class="step-icon">👤</div>
        <div class="step-title">Register Karo</div>
        <div class="step-desc">Free account banao, profile setup karo aur ₹50 welcome bonus pao.</div>
      </div>
      <div class="step">
        <div class="step-num">02</div>
        <div class="step-icon">🔍</div>
        <div class="step-title">Tournament Dhundo</div>
        <div class="step-desc">Apne budget aur skill level ke tournament browse karo aur join karo.</div>
      </div>
      <div class="step">
        <div class="step-num">03</div>
        <div class="step-icon">🎲</div>
        <div class="step-title">Khelo aur Jeeto</div>
        <div class="step-desc">Board par apni strategy use karo. Opponents ko block karo, tokens save karo, aur pehle finish karo.</div>
      </div>
      <div class="step">
        <div class="step-num">04</div>
        <div class="step-icon">💰</div>
        <div class="step-title">Withdraw Karo</div>
        <div class="step-desc">Prize jeeto aur seedha apne UPI par instant withdrawal karo.</div>
      </div>
    </div>
  </div>
</section>

<!-- GUIDE + FAQ CARDS -->
<section>
  <div class="section-inner">
    <div class="section-label">📚 Resources</div>
    <h2 class="section-title">Har Sawaal Ka Jawab<br><span style="color: var(--gold);">Yahan Hai</span></h2>
    <div class="guide-faq reveal">
      <div class="guide-card" onclick="openModal('guideModal')" style="cursor:pointer;">
        <div class="gc-icon gold-bg">📖</div>
        <div class="gc-title">Complete Player Guide</div>
        <div class="gc-desc">Ludo ke basic rules se lekar advanced tournament strategies tak — sab kuch ek jagah. Hinglish mein easy explanation.</div>
        <a class="btn-primary" style="margin-top:8px; padding: 12px 24px; font-size:15px;">Read Full Guide →</a>
      </div>
      <div class="faq-card" onclick="openModal('faqModal')" style="cursor:pointer;">
        <div class="gc-icon blue-bg">❓</div>
        <div class="gc-title">Frequently Asked Questions</div>
        <div class="gc-desc">Withdrawal, tournaments, rules, account — sabse common sawaalon ke jawab yahan milenge. Quick aur clear answers.</div>
        <a class="btn-secondary" style="margin-top:8px; padding: 12px 24px; font-size:15px;">View All FAQs →</a>
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
        <p>India ka sabse trusted real-money Ludo platform. Fair play, instant withdrawal, aur epic tournaments.</p>
      </div>
      <div class="footer-col">
        <h4>Game</h4>
        <a href="#tournaments">Tournaments</a>
        <a href="#" onclick="openModal('guideModal')">How to Play</a>
        <a href="#">Leaderboard</a>
        <a href="#">Practice Mode</a>
      </div>
      <div class="footer-col">
        <h4>Support</h4>
        <a href="#" onclick="openModal('faqModal')">FAQ</a>
        <a href="#">Contact Us</a>
        <a href="#">Report Issue</a>
        <a href="#">Community</a>
      </div>
      <div class="footer-col">
        <h4>Legal</h4>
        <a href="/terms">Terms of Service</a>
        <a href="/privacy">Privacy Policy</a>
        <a href="/fair-play">Fair Play Policy</a>
        <a href="/responsible-gaming">Responsible Gaming</a>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© 2025 RoxLudo. All rights reserved.</span>
      <span>Made with ❤️ for Ludo lovers across India 🇮🇳</span>
    </div>
  </div>
</footer>

<script>
  // ── TOURNAMENT DATA — fetched from Laravel API ──
  const FALLBACK_TOURNAMENTS = [
    { id:1, card_color:'gold',   status_badge:'live', status_text:'Live Now',          icon:'🔥', name:'Grand Sunday Classic', description:'128-player open tournament. Abhi sirf 23 seats baaki hain. Jaldi join karo!', meta1_label:'Prize Pool', meta1_value:'₹25,000', meta2_label:'Entry', meta2_value:'₹199',  meta3_label:'Players',    meta3_value:'105/128' },
    { id:2, card_color:'blue',   status_badge:'open', status_text:'Open Registration', icon:'⚡', name:'Rookie Rumble',        description:'New players ke liye special tournament. Max 2 months account age. Free entry!',  meta1_label:'Prize Pool', meta1_value:'₹5,000',  meta2_label:'Entry', meta2_value:'FREE',  meta3_label:'Players',    meta3_value:'56/64'   },
    { id:3, card_color:'purple', status_badge:'soon', status_text:'Starting Soon',     icon:'👑', name:'Champions League',     description:'Premium tournament for ranked players only. Top 10 rating required.',            meta1_label:'Prize Pool', meta1_value:'₹1,00,000',meta2_label:'Entry', meta2_value:'₹999', meta3_label:'Starts In',  meta3_value:'2h 14m'  }
  ];

  async function loadTournaments() {
    try {
      const res = await fetch('/api/homepage-cards');
      if (!res.ok) throw new Error('API error');
      const data = await res.json();
      renderTournaments(data.length ? data : FALLBACK_TOURNAMENTS);
    } catch(e) {
      renderTournaments(FALLBACK_TOURNAMENTS);
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
<?php /**PATH D:\Live-Code\Live-Rox-Ludo\games\backend_laravel\resources\views/ludo/landing.blade.php ENDPATH**/ ?>