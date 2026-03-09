<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$username = $_SESSION['username'];
$role     = $_SESSION['role'];
$user_id  = $_SESSION['user_id'];

function getHumidityStatus($h) {
    if ($h < 20)  return ['label'=>'Critically Dry','icon'=>'🏜️','color'=>'#C0392B','advice'=>'Immediate watering required! Your succulent is dangerously dehydrated.','bar'=>10];
    if ($h < 40)  return ['label'=>'Dry',           'icon'=>'☀️','color'=>'#E67E22','advice'=>'Your succulent is getting dry. Consider light watering soon.','bar'=>30];
    if ($h <= 60) return ['label'=>'Ideal',         'icon'=>'✅','color'=>'#27AE60','advice'=>'Perfect conditions! Your succulent is thriving.','bar'=>65];
    if ($h <= 80) return ['label'=>'Humid',         'icon'=>'💦','color'=>'#2980B9','advice'=>'Too humid. Improve ventilation to prevent root rot.','bar'=>80];
    return             ['label'=>'Critically Humid','icon'=>'🌊','color'=>'#8E44AD','advice'=>'Danger! Extremely high humidity. Root rot risk!','bar'=>98];
}

$result = null;
$db_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['humidity'])) {
    $h     = floatval($_POST['humidity']);
    $notes = htmlspecialchars(trim($_POST['notes'] ?? ''));

    if ($h >= 0 && $h <= 100) {
        $result          = getHumidityStatus($h);
        $result['value'] = $h;
        $result['notes'] = $notes;
        $status          = $result['label'];

        $db = getDB();

        $stmt = $db->prepare("INSERT INTO humidity (humidity_percent, status) VALUES (?, ?)");
        $stmt->bind_param("ds", $h, $status);
        $stmt->execute();
        $humidity_id = $stmt->insert_id;
        $stmt->close();

        $stmt = $db->prepare("INSERT INTO user_logs (user_id, humidity_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $humidity_id);
        $stmt->execute();
        $stmt->close();

        $db->close();
    } else {
        $db_error = 'Humidity must be between 0 and 100.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Record — SuccuTrack</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  :root{--sage:#7D9B76;--sage-light:#A8C5A0;--sage-dark:#4A6741;--cream:#F5F0E8;--terracotta:#C17F59;--sand:#E8D5B7;--dark:#1C2B1A;--muted:#6B7B6A;--white:#FEFDF9;--sidebar-w:260px;--border:rgba(125,155,118,0.2)}
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:'DM Sans',sans-serif;background:var(--cream);color:var(--dark);min-height:100vh;display:flex}
  .sidebar{width:var(--sidebar-w);min-height:100vh;background:var(--dark);display:flex;flex-direction:column;position:fixed;left:0;top:0;bottom:0;z-index:100}
  .sidebar-brand{padding:28px 24px 20px;border-bottom:1px solid rgba(255,255,255,0.07)}
  .brand-logo{display:flex;align-items:center;gap:10px}
  .brand-icon{width:38px;height:38px;background:linear-gradient(135deg,var(--sage-dark),var(--sage));border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px}
  .brand-name{font-family:'Playfair Display',serif;font-size:20px;font-weight:700;color:#fff}
  .brand-name em{font-style:italic;color:var(--sage-light)}
  .brand-desc{font-size:11px;color:rgba(255,255,255,0.4);margin-top:2px}
  .nav{padding:20px 12px;flex:1}
  .nav-label{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,0.3);padding:0 12px;margin-bottom:8px;margin-top:16px}
  .nav-link{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:10px;text-decoration:none;color:rgba(255,255,255,0.55);font-size:14px;transition:all .2s;margin-bottom:2px}
  .nav-link:hover{background:rgba(255,255,255,0.06);color:rgba(255,255,255,0.9)}
  .nav-link.active{background:linear-gradient(135deg,var(--sage-dark),var(--sage));color:#fff}
  .nav-icon{font-size:16px;width:20px;text-align:center}
  .sidebar-user{padding:20px 24px;border-top:1px solid rgba(255,255,255,0.07)}
  .user-info{display:flex;align-items:center;gap:10px;margin-bottom:12px}
  .user-avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--terracotta),#E8A87C);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:600;color:#fff}
  .user-name{font-size:13px;font-weight:500;color:rgba(255,255,255,0.85)}
  .user-role{font-size:11px;color:rgba(255,255,255,0.4)}
  .btn-logout{display:block;width:100%;padding:9px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:rgba(255,255,255,0.5);font-size:13px;text-align:center;text-decoration:none;transition:all .2s}
  .btn-logout:hover{background:rgba(193,127,89,0.2);color:#E8A87C}
  .main{margin-left:var(--sidebar-w);flex:1}
  .topbar{background:var(--white);border-bottom:1px solid var(--border);padding:18px 36px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50}
  .page-title{font-family:'Playfair Display',serif;font-size:24px;font-weight:700}
  .content{padding:36px;max-width:900px}
  .card{background:var(--white);border-radius:20px;padding:32px;border:1px solid var(--border);margin-bottom:24px;animation:fadeIn .5s both}
  @keyframes fadeIn{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
  .card-title{font-family:'Playfair Display',serif;font-size:22px;font-weight:700;margin-bottom:6px}
  .card-sub{font-size:14px;color:var(--muted);margin-bottom:28px}
  .field{margin-bottom:20px}
  .field label{display:block;font-size:12px;font-weight:500;letter-spacing:1px;text-transform:uppercase;color:var(--muted);margin-bottom:8px}
  .field input,.field textarea{width:100%;padding:13px 16px;border:1.5px solid var(--sand);border-radius:10px;font-family:'DM Sans',sans-serif;font-size:15px;color:var(--dark);background:var(--cream);outline:none;resize:vertical;transition:border-color .2s}
  .field input:focus,.field textarea:focus{border-color:var(--sage);background:#fff}
  .btn-submit{padding:15px 32px;background:linear-gradient(135deg,var(--sage-dark),var(--sage));border:none;border-radius:10px;cursor:pointer;font-family:'DM Sans',sans-serif;font-size:15px;font-weight:500;color:#fff;transition:all .25s;box-shadow:0 4px 14px rgba(74,103,65,0.3)}
  .btn-submit:hover{transform:translateY(-2px)}
  .btn-back{padding:15px 24px;background:transparent;border:1.5px solid var(--sand);border-radius:10px;font-family:'DM Sans',sans-serif;font-size:15px;color:var(--muted);text-decoration:none;display:inline-block;transition:all .2s}
  .btn-back:hover{border-color:var(--sage);color:var(--sage-dark)}
  .result-box{border-radius:16px;padding:24px;border:2px solid;animation:popIn .4s cubic-bezier(0.34,1.56,0.64,1)}
  @keyframes popIn{from{opacity:0;transform:scale(0.92)}to{opacity:1;transform:scale(1)}}
  .result-header{display:flex;align-items:center;gap:14px;margin-bottom:12px}
  .result-icon{font-size:44px}
  .result-label{font-family:'Playfair Display',serif;font-size:28px;font-weight:700}
  .result-percent{font-size:14px;opacity:.7;margin-top:2px}
  .result-advice{font-size:14px;line-height:1.6;opacity:.8;margin-bottom:16px}
  .result-bar-wrap{background:rgba(0,0,0,0.08);border-radius:10px;height:8px;overflow:hidden}
  .result-bar{height:100%;border-radius:10px}
  .success-toast{background:#E8F5E9;border:1px solid #A5D6A7;border-radius:10px;padding:14px 18px;margin-bottom:20px;font-size:14px;color:#2E7D32}
  .error-toast{background:#FFEBEE;border:1px solid #EF9A9A;border-radius:10px;padding:14px 18px;margin-bottom:20px;font-size:14px;color:#B71C1C}
  .preset-btn{padding:8px 16px;border-radius:20px;border:1.5px solid var(--sand);background:var(--cream);font-size:13px;color:var(--muted);cursor:pointer;transition:all .2s;font-family:'DM Sans',sans-serif;margin:4px 4px 0 0}
  .preset-btn:hover{border-color:var(--sage);color:var(--sage-dark);background:#fff}
  @media(max-width:700px){.sidebar{display:none}.main{margin-left:0}}
</style>
</head>
<body>
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-logo">
      <div class="brand-icon">🌵</div>
      <div><div class="brand-name">Succu<em>Track</em></div><div class="brand-desc">Humidity Monitor</div></div>
    </div>
  </div>
  <nav class="nav">
    <div class="nav-label">Monitor</div>
    <a class="nav-link" href="dashboard.php"><span class="nav-icon">📊</span> Dashboard</a>
    <a class="nav-link active" href="add_record.php"><span class="nav-icon">➕</span> Add Record</a>
    <a class="nav-link" href="logs.php"><span class="nav-icon">📋</span> All Logs</a>
    <?php if ($role === 'admin'): ?>
    <div class="nav-label">Admin</div>
    <a class="nav-link" href="admin_dashboard.php"><span class="nav-icon">🛡️</span> Admin Panel</a>
    <a class="nav-link" href="manage_users.php"><span class="nav-icon">👥</span> Manage Users</a>
    <?php endif; ?>
    <div class="nav-label">Account</div>
    <a class="nav-link" href="settings.php"><span class="nav-icon">⚙️</span> Settings</a>
  </nav>
  <div class="sidebar-user">
    <div class="user-info">
      <div class="user-avatar"><?= strtoupper(substr($username,0,1)) ?></div>
      <div><div class="user-name"><?= htmlspecialchars($username) ?></div><div class="user-role"><?= ucfirst($role) ?></div></div>
    </div>
    <a class="btn-logout" href="logout.php">Sign Out</a>
  </div>
</aside>

<div class="main">
  <div class="topbar"><div class="page-title">Add Humidity Record</div></div>
  <div class="content">
    <?php if ($db_error): ?>
    <div class="error-toast">⚠️ <?= htmlspecialchars($db_error) ?></div>
    <?php endif; ?>

    <?php if ($result): ?>
    <div class="success-toast">✅ Record saved to database! Status: <strong><?= $result['label'] ?></strong></div>
    <div class="card" style="animation-delay:.1s">
      <div class="card-title">Analysis Result</div>
      <div class="card-sub">Your reading has been recorded and analyzed</div>
      <div class="result-box" style="border-color:<?=$result['color']?>;background:<?=$result['color']?>18;color:<?=$result['color']?>">
        <div class="result-header">
          <div class="result-icon"><?=$result['icon']?></div>
          <div><div class="result-label"><?=$result['label']?></div><div class="result-percent"><?=number_format($result['value'],1)?>% humidity</div></div>
        </div>
        <div class="result-advice"><?=$result['advice']?></div>
        <?php if ($result['notes']): ?>
        <div style="font-size:13px;opacity:.7;margin-bottom:12px">📝 Notes: <?=$result['notes']?></div>
        <?php endif; ?>
        <div class="result-bar-wrap"><div class="result-bar" style="width:<?=$result['bar']?>%;background:<?=$result['color']?>"></div></div>
      </div>
      <div style="margin-top:20px;display:flex;gap:12px">
        <a href="add_record.php" class="btn-back">+ Add Another</a>
        <a href="dashboard.php" class="btn-submit" style="text-decoration:none;text-align:center">← Dashboard</a>
      </div>
    </div>

    <?php else: ?>
    <div class="card">
      <div class="card-title">Record Humidity Reading</div>
      <div class="card-sub">Enter current environmental conditions. This will be saved to the database.</div>
      <form method="POST">
        <div class="field">
          <label>Humidity Percentage *</label>
          <input type="number" name="humidity" min="0" max="100" step="0.1" placeholder="e.g. 52.5" required>
          <div style="margin-top:10px">
            <button type="button" class="preset-btn" onclick="setH(15)">🏜️ 15% Crit. Dry</button>
            <button type="button" class="preset-btn" onclick="setH(30)">☀️ 30% Dry</button>
            <button type="button" class="preset-btn" onclick="setH(50)">✅ 50% Ideal</button>
            <button type="button" class="preset-btn" onclick="setH(70)">💦 70% Humid</button>
            <button type="button" class="preset-btn" onclick="setH(90)">🌊 90% Crit. Humid</button>
          </div>
        </div>
        <div class="field">
          <label>Notes (Optional)</label>
          <textarea name="notes" rows="3" placeholder="e.g. After watering, near window sill…"></textarea>
        </div>
        <div style="display:flex;gap:12px">
          <a href="dashboard.php" class="btn-back">← Back</a>
          <button type="submit" class="btn-submit">💾 Save Record →</button>
        </div>
      </form>
    </div>
    <?php endif; ?>
  </div>
</div>
<script>function setH(v){document.querySelector('input[name=humidity]').value=v;}</script>
</body>
</html>
