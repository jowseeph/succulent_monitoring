<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT user_id, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $db->close();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SuccuTrack — Login</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root{--sage:#7D9B76;--sage-light:#A8C5A0;--sage-dark:#4A6741;--cream:#F5F0E8;--terracotta:#C17F59;--sand:#E8D5B7;--dark:#1C2B1A;--muted:#6B7B6A;--white:#FEFDF9}
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:'DM Sans',sans-serif;background:var(--cream);min-height:100vh;display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative}
  .bg-layer{position:fixed;inset:0;z-index:0;background:linear-gradient(135deg,#E8F0E6 0%,#F5F0E8 40%,#EDE4D3 100%)}
  .bg-circles{position:fixed;inset:0;z-index:0;overflow:hidden}
  .circle{position:absolute;border-radius:50%;opacity:0.15}
  .circle-1{width:500px;height:500px;background:var(--sage);top:-100px;left:-150px;animation:drift 18s ease-in-out infinite}
  .circle-2{width:350px;height:350px;background:var(--terracotta);bottom:-80px;right:-80px;animation:drift 22s ease-in-out infinite reverse}
  .circle-3{width:200px;height:200px;background:var(--sage-dark);top:40%;right:10%;animation:drift 14s ease-in-out infinite 3s}
  @keyframes drift{0%,100%{transform:translate(0,0) scale(1)}50%{transform:translate(20px,-30px) scale(1.05)}}
  .leaf-deco{position:fixed;z-index:0;opacity:0.07;font-size:200px;user-select:none}
  .leaf-1{top:-20px;right:-20px;transform:rotate(-30deg)}
  .leaf-2{bottom:-30px;left:-30px;transform:rotate(150deg)}
  .login-wrap{position:relative;z-index:1;display:flex;width:900px;max-width:96vw;background:var(--white);border-radius:24px;box-shadow:0 30px 80px rgba(28,43,26,0.15),0 0 0 1px rgba(125,155,118,0.15);overflow:hidden;animation:slideUp 0.7s cubic-bezier(0.16,1,0.3,1) both}
  @keyframes slideUp{from{opacity:0;transform:translateY(40px)}to{opacity:1;transform:translateY(0)}}
  .login-panel{flex:1;padding:60px 50px;display:flex;flex-direction:column;justify-content:center}
  .brand-panel{width:340px;flex-shrink:0;background:linear-gradient(160deg,var(--sage-dark) 0%,var(--sage) 60%,var(--sage-light) 100%);padding:60px 40px;display:flex;flex-direction:column;justify-content:center;align-items:flex-start;position:relative;overflow:hidden}
  .brand-panel::before{content:'🌵';position:absolute;font-size:220px;bottom:-40px;right:-40px;opacity:0.12;transform:rotate(-10deg)}
  .brand-panel::after{content:'🌿';position:absolute;font-size:120px;top:-20px;left:-10px;opacity:0.12;transform:rotate(20deg)}
  .brand-tag{font-size:11px;font-weight:500;letter-spacing:3px;color:rgba(255,255,255,0.6);text-transform:uppercase;margin-bottom:16px}
  .brand-title{font-family:'Playfair Display',serif;font-size:36px;font-weight:700;color:#fff;line-height:1.2;margin-bottom:8px}
  .brand-title em{font-style:italic;color:var(--sand)}
  .brand-sub{font-size:14px;color:rgba(255,255,255,0.7);line-height:1.6;margin-top:16px}
  .stat-chips{display:flex;flex-direction:column;gap:10px;margin-top:40px}
  .chip{background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.2);border-radius:10px;padding:10px 14px;display:flex;align-items:center;gap:10px;font-size:13px;color:rgba(255,255,255,0.85)}
  .form-title{font-family:'Playfair Display',serif;font-size:28px;font-weight:700;color:var(--dark);margin-bottom:6px}
  .form-sub{font-size:14px;color:var(--muted);margin-bottom:36px}
  .field{margin-bottom:20px}
  .field label{display:block;font-size:12px;font-weight:500;letter-spacing:1px;text-transform:uppercase;color:var(--muted);margin-bottom:8px}
  .field input{width:100%;padding:14px 16px;border:1.5px solid var(--sand);border-radius:10px;font-family:'DM Sans',sans-serif;font-size:15px;color:var(--dark);background:var(--cream);transition:all 0.2s;outline:none}
  .field input:focus{border-color:var(--sage);background:#fff;box-shadow:0 0 0 4px rgba(125,155,118,0.12)}
  .btn-login{width:100%;padding:15px;background:linear-gradient(135deg,var(--sage-dark),var(--sage));border:none;border-radius:10px;cursor:pointer;font-family:'DM Sans',sans-serif;font-size:15px;font-weight:500;color:#fff;transition:all 0.25s;margin-top:8px;box-shadow:0 4px 16px rgba(74,103,65,0.3)}
  .btn-login:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(74,103,65,0.4)}
  .error-msg{background:#FEF0EC;border:1px solid #F4C5AE;border-radius:8px;padding:12px 14px;font-size:13px;color:var(--terracotta);margin-bottom:20px}
  .demo-hint{margin-top:20px;text-align:center;font-size:12px;color:var(--muted);background:var(--cream);border-radius:8px;padding:12px}
  .demo-hint strong{color:var(--sage-dark)}
  @media(max-width:700px){.brand-panel{display:none}.login-panel{padding:40px 28px}}
</style>
</head>
<body>
<div class="bg-layer"></div>
<div class="bg-circles">
  <div class="circle circle-1"></div>
  <div class="circle circle-2"></div>
  <div class="circle circle-3"></div>
</div>
<span class="leaf-deco leaf-1">🌱</span>
<span class="leaf-deco leaf-2">🌿</span>
<div class="login-wrap">
  <div class="brand-panel">
    <div class="brand-tag">Succulent Care System</div>
    <div class="brand-title">Succu<em>Track</em></div>
    <div class="brand-title" style="font-size:18px;font-weight:400;color:rgba(255,255,255,0.8)">Humidity Monitor</div>
    <div class="brand-sub">Real-time insights to keep your succulents thriving. Monitor, log, and analyze environmental conditions effortlessly.</div>
    <div class="stat-chips">
      <div class="chip"><span style="font-size:18px">💧</span> Humidity Status Tracking</div>
      <div class="chip"><span style="font-size:18px">📊</span> Historical Log Analysis</div>
      <div class="chip"><span style="font-size:18px">👥</span> Multi-user Management</div>
    </div>
  </div>
  <div class="login-panel">
    <div class="form-title">Welcome back</div>
    <div class="form-sub">Sign in to your monitoring dashboard</div>
    <?php if ($error): ?>
    <div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="field">
        <label>Username</label>
        <input type="text" name="username" placeholder="Enter your username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
      </div>
      <div class="field">
        <label>Password</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn-login">Sign In →</button>
    </form>
    <div class="demo-hint">
      Demo: <strong>admin / admin123</strong> &nbsp;·&nbsp; <strong>user / user123</strong><br>
      <span style="font-size:11px;display:block;margin-top:4px;color:#999">Run <code>seed.php</code> once if this is your first time</span>
    </div>
  </div>
</div>
</body>
</html>
