<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — RoxLudo</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=Exo+2:wght@300;400;600;800&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
<style>
  *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
  :root{
    --gold:#FFD700;--gold-dark:#CC9900;
    --red:#E63946;--bg-dark:#0A0A14;--bg-card:#12121F;--bg-card2:#1A1A2E;
    --text:#F0F0FF;--text-muted:#8888AA;--border:rgba(255,215,0,0.15);
  }
  body{
    font-family:'Exo 2',sans-serif;
    background:var(--bg-dark);
    color:var(--text);
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
  }
  body::before{
    content:'';position:fixed;inset:0;
    background-image:
      linear-gradient(rgba(255,215,0,0.03) 1px,transparent 1px),
      linear-gradient(90deg,rgba(255,215,0,0.03) 1px,transparent 1px);
    background-size:60px 60px;pointer-events:none;z-index:0;
  }
  .orb{position:fixed;border-radius:50%;filter:blur(80px);pointer-events:none;}
  .orb-1{width:400px;height:400px;background:rgba(255,215,0,0.07);top:-100px;right:-100px;}
  .orb-2{width:300px;height:300px;background:rgba(230,57,70,0.08);bottom:-80px;left:-80px;}

  /* NAV */
  nav{
    position:fixed;top:0;left:0;right:0;z-index:100;
    display:flex;align-items:center;justify-content:space-between;
    padding:0 5%;height:66px;
    background:rgba(10,10,20,0.9);
    backdrop-filter:blur(20px);
    border-bottom:1px solid var(--border);
  }
  .nav-brand{
    font-family:'Orbitron',sans-serif;font-size:18px;font-weight:700;
    background:linear-gradient(135deg,var(--gold),#FF6B6B);
    -webkit-background-clip:text;-webkit-text-fill-color:transparent;
    background-clip:text;text-decoration:none;
  }
  .nav-back{color:var(--text-muted);text-decoration:none;font-size:14px;transition:color 0.2s;}
  .nav-back:hover{color:var(--gold);}

  /* CARD */
  .login-wrap{
    position:relative;z-index:1;
    width:100%;max-width:440px;
    padding:20px;
    margin-top:66px;
  }
  .login-card{
    background:var(--bg-card);
    border:1px solid var(--border);
    border-radius:24px;
    padding:40px 36px;
    box-shadow:0 40px 120px rgba(0,0,0,0.7),0 0 60px rgba(255,215,0,0.06);
  }
  .card-icon{
    width:64px;height:64px;
    background:linear-gradient(135deg,rgba(255,215,0,0.15),rgba(255,150,0,0.1));
    border:1px solid rgba(255,215,0,0.25);
    border-radius:18px;
    display:flex;align-items:center;justify-content:center;
    font-size:30px;
    margin:0 auto 20px;
  }
  .card-title{
    font-family:'Orbitron',sans-serif;
    font-size:22px;font-weight:700;
    text-align:center;margin-bottom:6px;
    background:linear-gradient(135deg,#fff,var(--gold));
    -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
  }
  .card-sub{text-align:center;font-size:14px;color:var(--text-muted);margin-bottom:32px;}

  /* FORM */
  .form-group{margin-bottom:18px;}
  .form-label{display:block;font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--text-muted);margin-bottom:8px;}
  .form-input{
    width:100%;padding:14px 16px;
    background:rgba(255,255,255,0.05);
    border:1px solid rgba(255,255,255,0.1);
    border-radius:12px;
    color:var(--text);
    font-family:'Exo 2',sans-serif;font-size:15px;
    outline:none;transition:all 0.2s;
  }
  .form-input:focus{border-color:var(--gold);background:rgba(255,215,0,0.04);box-shadow:0 0 0 3px rgba(255,215,0,0.08);}
  .form-input::placeholder{color:rgba(255,255,255,0.2);}

  .btn-submit{
    width:100%;padding:15px;margin-top:8px;
    background:linear-gradient(135deg,var(--gold),#FF9500);
    color:#000;
    font-family:'Rajdhani',sans-serif;font-size:17px;font-weight:700;
    letter-spacing:1px;text-transform:uppercase;
    border:none;border-radius:12px;cursor:pointer;
    transition:all 0.2s;
    box-shadow:0 8px 24px rgba(255,215,0,0.3);
  }
  .btn-submit:hover{transform:translateY(-2px);box-shadow:0 12px 32px rgba(255,215,0,0.45);}

  .divider{
    text-align:center;position:relative;
    color:var(--text-muted);font-size:13px;margin:22px 0;
  }
  .divider::before{
    content:'';position:absolute;top:50%;left:0;right:0;
    height:1px;background:rgba(255,255,255,0.07);
  }
  .divider span{background:var(--bg-card);padding:0 14px;position:relative;}

  .register-link{
    text-align:center;font-size:14px;color:var(--text-muted);
  }
  .register-link a{color:var(--gold);text-decoration:none;font-weight:600;}
  .register-link a:hover{text-decoration:underline;}

  /* ERRORS */
  .error-box{
    background:rgba(230,57,70,0.1);
    border:1px solid rgba(230,57,70,0.3);
    border-radius:10px;padding:12px 16px;
    margin-bottom:20px;font-size:14px;color:#FF6B6B;
  }
  .error-box ul{padding-left:18px;}
  .error-box li{margin-bottom:4px;}

  @media(max-width:480px){
    .login-card{padding:28px 22px;border-radius:18px;}
    .card-title{font-size:18px;}
  }
</style>
</head>
<body>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>

<nav>
  <a class="nav-brand" href="/">🎲 RoxLudo</a>
  <a class="nav-back" href="/">← Back to Home</a>
</nav>

<div class="login-wrap">
  <div class="login-card">

    <div class="card-icon">🎲</div>
    <div class="card-title">Player Login</div>
    <div class="card-sub">Enter the arena — your tournaments await</div>

    @if ($errors->any())
      <div class="error-box">
        <ul>
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @if (session('error'))
      <div class="error-box">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('user.login.submit') }}">
      @csrf

      <div class="form-group">
        <label class="form-label">Username / Email</label>
        <input class="form-input" type="text" name="identity"
          value="{{ old('identity') }}"
          placeholder="Enter your username or email"
          autocomplete="username" required>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <input class="form-input" type="password" name="password"
          placeholder="••••••••"
          autocomplete="current-password" required>
      </div>

      <button type="submit" class="btn-submit">🎯 Enter the Arena</button>
    </form>

    <div class="divider"><span>New Player?</span></div>
    <div class="register-link">Don't have an account? <a href="#">Register Free</a></div>

  </div>
</div>
</body>
</html>
