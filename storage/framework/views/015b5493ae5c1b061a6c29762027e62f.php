<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Responsible Gaming — RoxLudo</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=Exo+2:wght@300;400;600;800&family=Orbitron:wght@700&display=swap" rel="stylesheet">
<style>
  *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
  :root{--gold:#FFD700;--green:#06D6A0;--red:#E63946;--blue:#1A6BFF;--bg-dark:#0A0A14;--bg-card:#12121F;--bg-card2:#1A1A2E;--text:#F0F0FF;--text-muted:#8888AA;--border:rgba(255,215,0,0.15);}
  body{font-family:'Exo 2',sans-serif;background:var(--bg-dark);color:var(--text);min-height:100vh;}
  body::before{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(255,215,0,0.02) 1px,transparent 1px),linear-gradient(90deg,rgba(255,215,0,0.02) 1px,transparent 1px);background-size:60px 60px;pointer-events:none;z-index:0;}
  nav{position:fixed;top:0;left:0;right:0;z-index:100;display:flex;align-items:center;justify-content:space-between;padding:0 5%;height:66px;background:rgba(10,10,20,0.9);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);}
  .nav-brand{font-family:'Orbitron',sans-serif;font-size:18px;font-weight:700;background:linear-gradient(135deg,var(--gold),#FF6B6B);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;text-decoration:none;}
  .nav-back{color:var(--text-muted);text-decoration:none;font-size:14px;transition:color 0.2s;}
  .nav-back:hover{color:var(--gold);}
  .page-wrap{max-width:820px;margin:0 auto;padding:100px 5% 80px;position:relative;z-index:1;}
  .page-header{margin-bottom:48px;padding-bottom:28px;border-bottom:1px solid var(--border);}
  .page-tag{display:inline-block;background:rgba(230,57,70,0.1);border:1px solid rgba(230,57,70,0.3);color:var(--red);font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;padding:5px 14px;border-radius:50px;margin-bottom:16px;}
  h1{font-family:'Exo 2',sans-serif;font-size:clamp(28px,4vw,42px);font-weight:800;margin-bottom:12px;}
  .page-meta{font-size:14px;color:var(--text-muted);}
  .content-section{margin-bottom:36px;}
  h2{font-family:'Rajdhani',sans-serif;font-size:22px;font-weight:700;color:var(--gold);margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid rgba(255,215,0,0.1);}
  p{font-size:15px;color:var(--text-muted);line-height:1.8;margin-bottom:12px;}
  ul{padding-left:22px;color:var(--text-muted);font-size:15px;line-height:1.8;margin-bottom:12px;}
  li{margin-bottom:6px;}
  strong{color:var(--text);font-weight:600;}
  .warning-box{background:rgba(230,57,70,0.06);border:1px solid rgba(230,57,70,0.25);border-radius:12px;padding:20px 24px;margin:20px 0;}
  .warning-box p{margin:0;color:var(--text);}
  .signs-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin:20px 0;}
  .sign-item{background:var(--bg-card);border:1px solid rgba(230,57,70,0.15);border-radius:12px;padding:16px 18px;font-size:14px;color:var(--text-muted);display:flex;gap:10px;align-items:flex-start;}
  .sign-item span{font-size:20px;flex-shrink:0;margin-top:1px;}
  .tools-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin:20px 0;}
  .tool-card{background:var(--bg-card);border:1px solid var(--border);border-radius:14px;padding:22px;text-align:center;}
  .tool-icon{font-size:32px;margin-bottom:10px;}
  .tool-title{font-family:'Rajdhani',sans-serif;font-size:16px;font-weight:700;margin-bottom:6px;}
  .tool-desc{font-size:13px;color:var(--text-muted);line-height:1.6;}
  .selftest{background:var(--bg-card2);border:1px solid var(--border);border-radius:16px;padding:28px;margin:24px 0;}
  .selftest h3{font-family:'Rajdhani',sans-serif;font-size:19px;font-weight:700;margin-bottom:16px;color:var(--gold);}
  .test-q{display:flex;gap:12px;margin-bottom:14px;align-items:flex-start;font-size:14px;color:var(--text-muted);}
  .test-num{min-width:26px;height:26px;background:rgba(255,215,0,0.12);border:1px solid rgba(255,215,0,0.25);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:var(--gold);flex-shrink:0;margin-top:1px;}
  .help-box{background:linear-gradient(135deg,rgba(26,107,255,0.08),rgba(123,47,190,0.08));border:1px solid rgba(26,107,255,0.25);border-radius:16px;padding:28px;margin-top:32px;text-align:center;}
  .help-box h3{font-family:'Rajdhani',sans-serif;font-size:22px;font-weight:700;margin-bottom:10px;}
  .help-box p{margin-bottom:0;}
  .help-box a{color:var(--gold);text-decoration:none;font-weight:600;}
  .contact-box{background:var(--bg-card);border:1px solid var(--border);border-radius:16px;padding:28px;margin-top:28px;text-align:center;}
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
    <div class="page-tag">❤️ Responsible Gaming</div>
    <h1>Responsible Gaming</h1>
    <p class="page-meta">Your wellbeing is our priority &nbsp;•&nbsp; roxludo.com</p>
  </div>

  <div class="warning-box">
    <p>⚠️ <strong>Important:</strong> Gaming should always be fun. RoxLudo is a skill-based platform, but it involves real money. Please play responsibly, within your means, and never chase losses.</p>
  </div>

  <div class="content-section">
    <h2>🎯 Our Commitment to Responsible Gaming</h2>
    <p>At RoxLudo, we believe gaming should be an enjoyable form of entertainment — not a source of financial stress or addiction. We are committed to:</p>
    <ul>
      <li>Providing tools that help you control your spending and playing time</li>
      <li>Protecting vulnerable individuals, including minors</li>
      <li>Offering resources for those who may need help</li>
      <li>Training our team to identify and assist problem gamers</li>
      <li>Maintaining strict age verification to prevent underage gaming</li>
    </ul>
  </div>

  <div class="content-section">
    <h2>🚨 Warning Signs of Problem Gaming</h2>
    <p>Be honest with yourself. If you recognize any of these signs, it may be time to take a break:</p>
    <div class="signs-grid">
      <div class="sign-item"><span>💸</span>Spending more money than you can afford on entry fees</div>
      <div class="sign-item"><span>😰</span>Feeling anxious, irritable, or restless when not playing</div>
      <div class="sign-item"><span>🔄</span>Chasing losses by playing more to "win back" money</div>
      <div class="sign-item"><span>🙈</span>Hiding your gaming or winnings/losses from family</div>
      <div class="sign-item"><span>⏰</span>Playing for much longer than you intended</div>
      <div class="sign-item"><span>🧠</span>Gaming is affecting your work, studies, or relationships</div>
      <div class="sign-item"><span>💳</span>Borrowing money to fund your gaming</div>
      <div class="sign-item"><span>😴</span>Losing sleep due to late-night gaming sessions</div>
    </div>
  </div>

  <div class="content-section">
    <h2>🛠️ Our Responsible Gaming Tools</h2>
    <div class="tools-grid">
      <div class="tool-card">
        <div class="tool-icon">💰</div>
        <div class="tool-title">Deposit Limits</div>
        <div class="tool-desc">Set daily, weekly, or monthly deposit limits on your account. Changes to limits take 24 hours to increase.</div>
      </div>
      <div class="tool-card">
        <div class="tool-icon">⏸️</div>
        <div class="tool-title">Session Time Limits</div>
        <div class="tool-desc">Set alerts when you've been playing for a certain duration. Take automatic breaks after set periods.</div>
      </div>
      <div class="tool-card">
        <div class="tool-icon">❄️</div>
        <div class="tool-title">Cool-off Period</div>
        <div class="tool-desc">Take a break from 24 hours to 6 weeks. Account access is restricted during this period.</div>
      </div>
      <div class="tool-card">
        <div class="tool-icon">🚫</div>
        <div class="tool-title">Self-Exclusion</div>
        <div class="tool-desc">Permanently or temporarily exclude yourself from the platform. We will not send you marketing during exclusion.</div>
      </div>
      <div class="tool-card">
        <div class="tool-icon">🗓️</div>
        <div class="tool-title">Activity History</div>
        <div class="tool-desc">View a full history of your deposits, withdrawals, and time spent gaming at any time.</div>
      </div>
      <div class="tool-card">
        <div class="tool-icon">🔔</div>
        <div class="tool-title">Reality Checks</div>
        <div class="tool-desc">Get periodic reminders showing how long you've been playing and your net win/loss for the session.</div>
      </div>
    </div>
    <p style="margin-top:16px;">To access these tools, go to <strong>Settings → Responsible Gaming</strong> in your account dashboard.</p>
  </div>

  <div class="content-section">
    <h2>📝 Self-Assessment Test</h2>
    <div class="selftest">
      <h3>Ask yourself honestly:</h3>
      <div class="test-q"><div class="test-num">1</div><div>Do you spend more time or money on gaming than you initially plan?</div></div>
      <div class="test-q"><div class="test-num">2</div><div>Do you play to escape problems, feelings of depression, or anxiety?</div></div>
      <div class="test-q"><div class="test-num">3</div><div>Have your family or friends expressed concern about your gaming habits?</div></div>
      <div class="test-q"><div class="test-num">4</div><div>Do you feel restless or irritable when trying to cut down on gaming?</div></div>
      <div class="test-q"><div class="test-num">5</div><div>Have you ever lied to someone about how much you play or spend?</div></div>
      <p style="margin-top:16px;padding-top:16px;border-top:1px solid rgba(255,255,255,0.06);">If you answered <strong>yes to 2 or more</strong> questions, we strongly encourage you to use our self-exclusion tools or seek professional support.</p>
    </div>
  </div>

  <div class="content-section">
    <h2>👶 Protection of Minors</h2>
    <p>RoxLudo is strictly for users aged <strong>18 and above</strong>. We take the protection of minors very seriously:</p>
    <ul>
      <li>Age verification is required during account registration and for withdrawals</li>
      <li>Accounts found to belong to minors are immediately terminated</li>
      <li>Parents/guardians can contact us to report underage account usage</li>
      <li>We recommend using parental control software if children use shared devices</li>
    </ul>
  </div>

  <div class="help-box">
    <h3>🆘 Need Help?</h3>
    <p>You are not alone. If gaming is causing you distress, please reach out:</p>
    <p style="margin-top:12px;font-size:14px;color:var(--text-muted);">
      Our Responsible Gaming team: <a href="mailto:support@roxludo.com">support@roxludo.com</a><br>
      iGaming Addiction Helpline India: <strong>1800-XXX-XXXX</strong> (Toll Free)<br>
      We are here to help, not judge. 💙
    </p>
  </div>

  <div class="contact-box">
    <h3>📧 Contact Responsible Gaming Team</h3>
    <p style="color:var(--text-muted);margin:8px 0;">Email: <a href="mailto:responsible@roxludo.com">responsible@roxludo.com</a></p>
    <p style="color:var(--text-muted);font-size:13px;margin-top:8px;">All communications are confidential. We respond within 24 hours.</p>
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
<?php /**PATH D:\Live-Code\Live-Rox-Ludo\games\backend_laravel\resources\views/ludo/responsible-gaming.blade.php ENDPATH**/ ?>