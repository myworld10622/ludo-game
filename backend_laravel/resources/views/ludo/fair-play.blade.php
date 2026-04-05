<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fair Play Policy — RoxLudo</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=Exo+2:wght@300;400;600;800&family=Orbitron:wght@700&display=swap" rel="stylesheet">
<style>
  *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
  :root{--gold:#FFD700;--green:#06D6A0;--red:#E63946;--bg-dark:#0A0A14;--bg-card:#12121F;--text:#F0F0FF;--text-muted:#8888AA;--border:rgba(255,215,0,0.15);}
  body{font-family:'Exo 2',sans-serif;background:var(--bg-dark);color:var(--text);min-height:100vh;}
  body::before{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(255,215,0,0.02) 1px,transparent 1px),linear-gradient(90deg,rgba(255,215,0,0.02) 1px,transparent 1px);background-size:60px 60px;pointer-events:none;z-index:0;}
  nav{position:fixed;top:0;left:0;right:0;z-index:100;display:flex;align-items:center;justify-content:space-between;padding:0 5%;height:66px;background:rgba(10,10,20,0.9);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);}
  .nav-brand{font-family:'Orbitron',sans-serif;font-size:18px;font-weight:700;background:linear-gradient(135deg,var(--gold),#FF6B6B);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;text-decoration:none;}
  .nav-back{color:var(--text-muted);text-decoration:none;font-size:14px;transition:color 0.2s;}
  .nav-back:hover{color:var(--gold);}
  .page-wrap{max-width:820px;margin:0 auto;padding:100px 5% 80px;position:relative;z-index:1;}
  .page-header{margin-bottom:48px;padding-bottom:28px;border-bottom:1px solid var(--border);}
  .page-tag{display:inline-block;background:rgba(6,214,160,0.1);border:1px solid rgba(6,214,160,0.3);color:var(--green);font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;padding:5px 14px;border-radius:50px;margin-bottom:16px;}
  h1{font-family:'Exo 2',sans-serif;font-size:clamp(28px,4vw,42px);font-weight:800;margin-bottom:12px;}
  .page-meta{font-size:14px;color:var(--text-muted);}
  .content-section{margin-bottom:36px;}
  h2{font-family:'Rajdhani',sans-serif;font-size:22px;font-weight:700;color:var(--gold);margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid rgba(255,215,0,0.1);}
  p{font-size:15px;color:var(--text-muted);line-height:1.8;margin-bottom:12px;}
  ul{padding-left:22px;color:var(--text-muted);font-size:15px;line-height:1.8;margin-bottom:12px;}
  li{margin-bottom:6px;}
  strong{color:var(--text);font-weight:600;}
  .rule-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;margin:20px 0;}
  .rule-card{background:var(--bg-card);border-radius:14px;padding:22px;border:1px solid rgba(255,255,255,0.06);}
  .rule-card.allowed{border-top:3px solid var(--green);}
  .rule-card.forbidden{border-top:3px solid var(--red);}
  .rule-card h3{font-family:'Rajdhani',sans-serif;font-size:17px;font-weight:700;margin-bottom:12px;}
  .rule-card.allowed h3{color:var(--green);}
  .rule-card.forbidden h3{color:var(--red);}
  .rule-card ul{margin:0;font-size:14px;}
  .penalty-table{width:100%;border-collapse:collapse;margin:16px 0;font-size:14px;}
  .penalty-table th{background:rgba(255,215,0,0.08);color:var(--gold);font-family:'Rajdhani',sans-serif;font-weight:700;padding:12px 16px;text-align:left;border-bottom:1px solid var(--border);}
  .penalty-table td{padding:12px 16px;border-bottom:1px solid rgba(255,255,255,0.04);color:var(--text-muted);}
  .penalty-table tr:last-child td{border-bottom:none;}
  .badge-warn{display:inline-block;background:rgba(230,57,70,0.12);color:var(--red);font-size:12px;font-weight:700;padding:3px 10px;border-radius:50px;border:1px solid rgba(230,57,70,0.25);}
  .badge-ok{display:inline-block;background:rgba(6,214,160,0.12);color:var(--green);font-size:12px;font-weight:700;padding:3px 10px;border-radius:50px;border:1px solid rgba(6,214,160,0.25);}
  .contact-box{background:var(--bg-card);border:1px solid var(--border);border-radius:16px;padding:28px;margin-top:48px;text-align:center;}
  .contact-box h3{font-family:'Rajdhani',sans-serif;font-size:20px;font-weight:700;margin-bottom:8px;}
  .contact-box a{color:var(--gold);text-decoration:none;font-weight:600;}
  footer{text-align:center;padding:30px 5%;border-top:1px solid var(--border);color:var(--text-muted);font-size:13px;margin-top:60px;}
  footer a{color:var(--text-muted);text-decoration:none;margin:0 10px;}
  footer a:hover{color:var(--gold);}
</style>
</head>
<body>
<nav>
  <a class="nav-brand" href="/">🎲 RoxLudo</a>
  <a class="nav-back" href="/">← Back to Home</a>
</nav>

<div class="page-wrap">
  <div class="page-header">
    <div class="page-tag">🛡️ Fair Play</div>
    <h1>Fair Play Policy</h1>
    <p class="page-meta">Last Updated: January 1, 2025 &nbsp;•&nbsp; roxludo.com</p>
  </div>

  <p style="font-size:16px;line-height:1.8;margin-bottom:32px;">RoxLudo is committed to providing a <strong>fair, competitive, and enjoyable</strong> gaming environment for all players. This Fair Play Policy outlines our standards, prohibited behaviors, and enforcement measures.</p>

  <div class="content-section">
    <h2>🎲 Our Fair Play Commitment</h2>
    <p>RoxLudo operates as a <strong>skill-based gaming platform</strong>. Every player deserves an equal and honest chance to compete. We use advanced anti-cheat systems, behavioral analysis, and player reporting to maintain integrity at all times.</p>
    <p>Our dice algorithm is <strong>certified random</strong> and audited regularly. No player or administrator can influence dice outcomes.</p>
  </div>

  <div class="content-section">
    <h2>✅ Allowed vs. ❌ Forbidden</h2>
    <div class="rule-cards">
      <div class="rule-card allowed">
        <h3>✅ Allowed</h3>
        <ul>
          <li>Using strategic moves and blocking tactics</li>
          <li>Safe square defense strategies</li>
          <li>Choosing which token to move</li>
          <li>Timing your kills strategically</li>
          <li>Playing in multiple tournaments simultaneously</li>
          <li>Sharing strategies publicly</li>
        </ul>
      </div>
      <div class="rule-card forbidden">
        <h3>❌ Forbidden</h3>
        <ul>
          <li>Using bots or auto-clickers</li>
          <li>Collusion with opponents</li>
          <li>Multiple accounts</li>
          <li>Using VPN to manipulate region</li>
          <li>Exploiting platform bugs</li>
          <li>Account sharing or selling</li>
        </ul>
      </div>
    </div>
  </div>

  <div class="content-section">
    <h2>🔍 Cheating Detection</h2>
    <p>RoxLudo uses a multi-layer detection system:</p>
    <ul>
      <li><strong>Behavioral Analysis:</strong> AI monitors move patterns for bot-like consistency</li>
      <li><strong>Timing Analysis:</strong> Inhuman response times trigger automatic flags</li>
      <li><strong>Device Fingerprinting:</strong> Detects multiple accounts from the same device</li>
      <li><strong>IP Monitoring:</strong> Flags suspicious login patterns and location anomalies</li>
      <li><strong>Collusion Detection:</strong> Identifies suspicious patterns between specific players</li>
      <li><strong>Player Reports:</strong> Community-driven reporting reviewed by our team</li>
    </ul>
  </div>

  <div class="content-section">
    <h2>⚠️ Penalties & Enforcement</h2>
    <table class="penalty-table">
      <thead>
        <tr><th>Violation</th><th>First Offense</th><th>Repeat Offense</th></tr>
      </thead>
      <tbody>
        <tr><td>Unsportsmanlike conduct</td><td><span class="badge-warn">Warning + Chat ban</span></td><td><span class="badge-warn">7-day suspension</span></td></tr>
        <tr><td>Intentional disconnection</td><td><span class="badge-warn">Match forfeit</span></td><td><span class="badge-warn">30-day ban</span></td></tr>
        <tr><td>Using bots/automation</td><td><span class="badge-warn">Permanent ban</span></td><td>N/A</td></tr>
        <tr><td>Multiple accounts</td><td><span class="badge-warn">All accounts banned</span></td><td>N/A</td></tr>
        <tr><td>Collusion</td><td><span class="badge-warn">Permanent ban + Winnings forfeited</span></td><td>N/A</td></tr>
        <tr><td>Fraudulent withdrawals</td><td><span class="badge-warn">Permanent ban + Legal action</span></td><td>N/A</td></tr>
      </tbody>
    </table>
    <p>Banned accounts forfeit all pending winnings. No refunds are issued on entry fees paid during the violation period.</p>
  </div>

  <div class="content-section">
    <h2>🚨 Reporting Violations</h2>
    <p>If you suspect a player of cheating or violating fair play rules:</p>
    <ul>
      <li>Use the <strong>in-game "Report" button</strong> on the player's profile</li>
      <li>Email <strong>fairplay@roxludo.com</strong> with evidence (screenshots, game ID)</li>
      <li>Include: Your username, opponent's username, game ID, and description of violation</li>
    </ul>
    <p>All reports are reviewed within <strong>48 hours</strong>. We take every report seriously and protect the reporter's identity.</p>
  </div>

  <div class="content-section">
    <h2>⚖️ Appeals Process</h2>
    <p>If you believe your account was penalized unfairly:</p>
    <ul>
      <li>Submit an appeal to <strong>appeals@roxludo.com</strong> within 7 days of the penalty</li>
      <li>Include your username, the penalty received, and your explanation</li>
      <li>Appeals are reviewed by a dedicated team within 5 business days</li>
      <li>Our decision on appeals is final</li>
    </ul>
  </div>

  <div class="contact-box">
    <h3>🛡️ Report a Violation</h3>
    <p style="color:var(--text-muted);margin:8px 0;">Email us at <a href="mailto:fairplay@roxludo.com">fairplay@roxludo.com</a> with your report</p>
    <p style="color:var(--text-muted);font-size:13px;margin-top:8px;">Together we keep RoxLudo fair for everyone 🎲</p>
  </div>
</div>

<footer>
  <p>© 2025 RoxLudo &nbsp;•&nbsp; roxludo.com</p>
  <div style="margin-top:10px;">
    <a href="/terms">Terms of Service</a>
    <a href="/privacy">Privacy Policy</a>
    <a href="/fair-play">Fair Play Policy</a>
    <a href="/responsible-gaming">Responsible Gaming</a>
    <a href="/">Home</a>
  </div>
</footer>
</body>
</html>
