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
    return             ['label'=>'Critically Humid','icon'=>'🌊','color'=>'#8E44AD','advice'=>'Danger! Extremely high humidity. Risk of fungal disease and root rot.','bar'=>98];
}

$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['humidity'])) {
    $humidity = floatval($_POST['humidity']);
    if ($humidity >= 0 && $humidity <= 100) {
        $result  = getHumidityStatus($humidity);
        $status  = $result['label'];
        $result['value'] = $humidity;

        $db = getDB();

        // Insert into humidity table
        $stmt = $db->prepare("INSERT INTO humidity (humidity_percent, status) VALUES (?, ?)");
        $stmt->bind_param("ds", $humidity, $status);
        $stmt->execute();
        $humidity_id = $stmt->insert_id;
        $stmt->close();

        // Insert into user_logs
        $stmt = $db->prepare("INSERT INTO user_logs (user_id, humidity_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $humidity_id);
        $stmt->execute();
        $stmt->close();

        $db->close();
    }
}

// Fetch recent logs for this user from DB
$db   = getDB();
$stmt = $db->prepare("
    SELECT h.humidity_percent, h.status, h.recorded_at
    FROM user_logs ul
    JOIN humidity h ON ul.humidity_id = h.humidity_id
    WHERE ul.user_id = ?
    ORDER BY h.recorded_at DESC
    LIMIT 10
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Stats for top bar
$total_readings = $db->query("SELECT COUNT(*) as c FROM user_logs WHERE user_id=$user_id")->fetch_assoc()['c'];
$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — SuccuTrack</title>
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
  .badge-role{padding:4px 12px;border-radius:20px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:1px;background:linear-gradient(135deg,var(--sage-dark),var(--sage));color:#fff}
  .content{padding:36px}
  .stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:32px}
  .stat-card{background:var(--white);border-radius:16px;padding:20px 22px;border:1px solid var(--border);animation:fadeIn 0.5s both;transition:transform .2s,box-shadow .2s}
  .stat-card:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(28,43,26,0.08)}
  .stat-card:nth-child(2){animation-delay:.08s}.stat-card:nth-child(3){animation-delay:.14s}.stat-card:nth-child(4){animation-delay:.2s}
  @keyframes fadeIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
  .stat-icon{font-size:22px;margin-bottom:6px}
  .stat-label{font-size:11px;letter-spacing:1px;text-transform:uppercase;color:var(--muted)}
  .stat-value{font-size:26px;font-weight:600;color:var(--dark);font-family:'Playfair Display',serif;margin-top:2px}
  .stat-sub{font-size:12px;color:var(--muted)}
  .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:28px}
  .card{background:var(--white);border-radius:20px;padding:28px;border:1px solid var(--border);animation:fadeIn 0.5s 0.2s both}
  .card-title{font-family:'Playfair Display',serif;font-size:18px;font-weight:700;margin-bottom:6px}
  .card-sub{font-size:13px;color:var(--muted);margin-bottom:24px}
  .humidity-input-wrap{display:flex;flex-direction:column;gap:16px}
  .slider-label{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
  .slider-val{font-size:40px;font-weight:700;font-family:'Playfair Display',serif;line-height:1}
  .slider-val sup{font-size:18px;font-weight:400;color:var(--muted)}
  input[type=range]{width:100%;-webkit-appearance:none;height:6px;border-radius:10px;outline:none;margin:12px 0}
  input[type=range]::-webkit-slider-thumb{-webkit-appearance:none;width:22px;height:22px;border-radius:50%;background:var(--sage-dark);border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.2);cursor:pointer;transition:transform .2s}
  input[type=range]::-webkit-slider-thumb:hover{transform:scale(1.2)}
  .range-labels{display:flex;justify-content:space-between;font-size:11px;color:var(--muted)}
  .number-input-wrap{display:flex;gap:10px;align-items:center}
  .number-input-wrap input[type=number]{flex:1;padding:12px 16px;border:1.5px solid var(--sand);border-radius:10px;font-family:'DM Sans',sans-serif;font-size:18px;font-weight:600;color:var(--dark);background:var(--cream);outline:none;text-align:center;transition:border-color .2s}
  .number-input-wrap input[type=number]:focus{border-color:var(--sage);background:#fff}
  .btn-submit{flex:1;padding:13px 20px;background:linear-gradient(135deg,var(--sage-dark),var(--sage));border:none;border-radius:10px;cursor:pointer;font-family:'DM Sans',sans-serif;font-size:15px;font-weight:500;color:#fff;transition:all .25s;box-shadow:0 4px 14px rgba(74,103,65,0.3)}
  .btn-submit:hover{transform:translateY(-2px)}
  .result-display{border-radius:16px;padding:24px;border:2px solid;animation:popIn .4s cubic-bezier(0.34,1.56,0.64,1)}
  @keyframes popIn{from{opacity:0;transform:scale(0.9)}to{opacity:1;transform:scale(1)}}
  .result-header{display:flex;align-items:center;gap:14px;margin-bottom:14px}
  .result-icon{font-size:40px}
  .result-label{font-family:'Playfair Display',serif;font-size:26px;font-weight:700}
  .result-percent{font-size:14px;opacity:.7;margin-top:2px}
  .result-advice{font-size:14px;line-height:1.6;opacity:.8;margin-bottom:16px}
  .result-bar-wrap{background:rgba(0,0,0,0.08);border-radius:10px;height:8px;overflow:hidden}
  .result-bar{height:100%;border-radius:10px}
  .placeholder-state{text-align:center;padding:32px 20px;color:var(--muted)}
  .placeholder-icon{font-size:48px;margin-bottom:10px;opacity:.4}
  .log-table-wrap{border-radius:12px;border:1px solid var(--border);overflow:auto}
  table{width:100%;border-collapse:collapse}
  thead tr{background:var(--cream)}
  th{padding:12px 16px;text-align:left;font-size:11px;letter-spacing:1px;text-transform:uppercase;color:var(--muted);font-weight:500}
  td{padding:13px 16px;font-size:14px;border-top:1px solid var(--border);vertical-align:middle}
  tr:hover td{background:rgba(125,155,118,0.04)}
  .status-pill{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:500}
  .pill-Ideal{background:#E8F5E9;color:#2E7D32}.pill-Dry{background:#FFF3E0;color:#E65100}
  .pill-Humid{background:#E3F2FD;color:#1565C0}.pill-Critically-Dry{background:#FFEBEE;color:#B71C1C}
  .pill-Critically-Humid{background:#F3E5F5;color:#6A1B9A}
  .chart-zones{display:flex;gap:8px;margin-top:14px;flex-wrap:wrap}
  .zone-badge{display:flex;align-items:center;gap:6px;font-size:11px;color:var(--muted);padding:4px 10px;background:var(--cream);border-radius:20px}
  .zone-dot{width:8px;height:8px;border-radius:50%}
  .tips-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
  .tip-card{background:var(--cream);border-radius:12px;padding:16px;border-left:3px solid}
  .tip-title{font-size:13px;font-weight:600;margin-bottom:4px}
  .tip-text{font-size:12px;color:var(--muted);line-height:1.5}
  @media(max-width:900px){.sidebar{display:none}.main{margin-left:0}.stats-row{grid-template-columns:1fr 1fr}.grid-2{grid-template-columns:1fr}.tips-grid{grid-template-columns:1fr}}
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
    <a class="nav-link active" href="dashboard.php"><span class="nav-icon">📊</span> Dashboard</a>
    <a class="nav-link" href="add_record.php"><span class="nav-icon">➕</span> Add Record</a>
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
  <div class="topbar">
    <div class="page-title">Dashboard</div>
    <div style="display:flex;align-items:center;gap:14px">
      <span style="font-size:13px;color:var(--muted)" id="clock"></span>
      <span class="badge-role"><?= ucfirst($role) ?></span>
    </div>
  </div>
  <div class="content">
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon">📊</div>
        <div class="stat-label">Total Readings</div>
        <div class="stat-value"><?= $total_readings ?></div>
        <div class="stat-sub">All time</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">💧</div>
        <div class="stat-label">Last Reading</div>
        <div class="stat-value"><?= isset($logs[0]) ? number_format($logs[0]['humidity_percent'],1).'%' : '—' ?></div>
        <div class="stat-sub"><?= isset($logs[0]) ? $logs[0]['status'] : 'No data yet' ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🎯</div>
        <div class="stat-label">Ideal Range</div>
        <div class="stat-value">40–60</div>
        <div class="stat-sub">Percent humidity</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🌡️</div>
        <div class="stat-label">Current Status</div>
        <div class="stat-value" style="font-size:15px;padding-top:6px"><?= isset($logs[0]) ? $logs[0]['status'] : 'Waiting…' ?></div>
        <div class="stat-sub">Latest condition</div>
      </div>
    </div>

    <div class="grid-2">
      <div class="card">
        <div class="card-title">Input Humidity</div>
        <div class="card-sub">Slide or type to enter the current humidity level</div>
        <form method="POST" class="humidity-input-wrap">
          <div>
            <div class="slider-label">
              <span style="font-size:13px;color:var(--muted)">Humidity Level</span>
              <div class="slider-val" id="sliderDisplay"><?= isset($_POST['humidity']) ? htmlspecialchars($_POST['humidity']) : '50' ?><sup>%</sup></div>
            </div>
            <input type="range" id="humiditySlider" min="0" max="100" step="0.5"
              value="<?= isset($_POST['humidity']) ? htmlspecialchars($_POST['humidity']) : '50' ?>"
              oninput="syncInputs(this.value)">
            <div class="range-labels"><span>0%</span><span>25%</span><span>50%</span><span>75%</span><span>100%</span></div>
          </div>
          <div class="number-input-wrap">
            <input type="number" id="humidityNum" name="humidity" min="0" max="100" step="0.1"
              value="<?= isset($_POST['humidity']) ? htmlspecialchars($_POST['humidity']) : '50' ?>"
              placeholder="Enter %" required oninput="syncSlider(this.value)">
            <button type="submit" class="btn-submit">Analyze →</button>
          </div>
        </form>
        <div class="chart-zones" style="margin-top:20px">
          <div class="zone-badge"><div class="zone-dot" style="background:#C0392B"></div> Crit. Dry &lt;20%</div>
          <div class="zone-badge"><div class="zone-dot" style="background:#E67E22"></div> Dry 20–39%</div>
          <div class="zone-badge"><div class="zone-dot" style="background:#27AE60"></div> Ideal 40–60%</div>
          <div class="zone-badge"><div class="zone-dot" style="background:#2980B9"></div> Humid 61–80%</div>
          <div class="zone-badge"><div class="zone-dot" style="background:#8E44AD"></div> Crit. Humid &gt;80%</div>
        </div>
      </div>

      <div class="card">
        <div class="card-title">Status Projection</div>
        <div class="card-sub">Environmental assessment for your succulent</div>
        <?php if ($result): ?>
        <div class="result-display" style="border-color:<?=$result['color']?>;background:<?=$result['color']?>18;color:<?=$result['color']?>">
          <div class="result-header">
            <div class="result-icon"><?=$result['icon']?></div>
            <div>
              <div class="result-label"><?=$result['label']?></div>
              <div class="result-percent"><?=number_format($result['value'],1)?>% recorded</div>
            </div>
          </div>
          <div class="result-advice"><?=$result['advice']?></div>
          <div class="result-bar-wrap"><div class="result-bar" style="width:<?=$result['bar']?>%;background:<?=$result['color']?>"></div></div>
        </div>
        <div style="margin-top:12px;font-size:12px;color:var(--muted)">✅ Saved to database — <?= date('M j, Y g:i A') ?></div>
        <?php else: ?>
        <div class="placeholder-state">
          <div class="placeholder-icon">🌵</div>
          <div style="font-size:14px">Enter a humidity value and click <strong>Analyze</strong> to see status</div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="grid-2">
      <div class="card">
        <div class="card-title">Recent Logs</div>
        <div class="card-sub">Your last 10 humidity records from the database</div>
        <div class="log-table-wrap">
          <table>
            <thead><tr><th>#</th><th>Humidity</th><th>Status</th><th>Recorded At</th></tr></thead>
            <tbody>
              <?php if (empty($logs)): ?>
              <tr><td colspan="4" style="text-align:center;color:var(--muted);padding:28px;font-style:italic">No records yet — add your first reading!</td></tr>
              <?php else: foreach ($logs as $i => $log):
                $pc = 'pill-' . str_replace(' ', '-', $log['status']); ?>
              <tr>
                <td style="color:var(--muted)"><?= $i+1 ?></td>
                <td><strong><?= number_format($log['humidity_percent'],1) ?>%</strong></td>
                <td><span class="status-pill <?= $pc ?>"><?= htmlspecialchars($log['status']) ?></span></td>
                <td style="color:var(--muted);font-size:12px"><?= date('M j, g:i A', strtotime($log['recorded_at'])) ?></td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <div class="card-title">Care Guide 🌿</div>
        <div class="card-sub">Succulent humidity best practices</div>
        <div class="tips-grid">
          <div class="tip-card" style="border-color:#27AE60"><div class="tip-title">✅ Ideal Range</div><div class="tip-text">Keep humidity 40–60%. Succulents prefer semi-arid conditions.</div></div>
          <div class="tip-card" style="border-color:#E67E22"><div class="tip-title">☀️ Dry Conditions</div><div class="tip-text">Below 40%? Water deeply but allow full drainage before next watering.</div></div>
          <div class="tip-card" style="border-color:#2980B9"><div class="tip-title">💦 High Humidity</div><div class="tip-text">Above 60%? Improve air circulation and avoid misting.</div></div>
          <div class="tip-card" style="border-color:#8E44AD"><div class="tip-title">⚠️ Warning Signs</div><div class="tip-text">Mushy leaves = overwatering. Shriveled leaves = underwatering.</div></div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  function updateClock(){document.getElementById('clock').textContent=new Date().toLocaleTimeString();}
  updateClock();setInterval(updateClock,1000);
  function syncInputs(v){document.getElementById('humidityNum').value=parseFloat(v).toFixed(1);document.getElementById('sliderDisplay').innerHTML=parseFloat(v).toFixed(1)+'<sup>%</sup>';updateGradient(v);}
  function syncSlider(v){document.getElementById('humiditySlider').value=v;document.getElementById('sliderDisplay').innerHTML=parseFloat(v||0).toFixed(1)+'<sup>%</sup>';updateGradient(v);}
  function updateGradient(v){const s=document.getElementById('humiditySlider');s.style.background=`linear-gradient(to right,#4A6741 0%,#7D9B76 ${v}%,#E8D5B7 ${v}%,#E8D5B7 100%)`;}
  updateGradient(<?= isset($_POST['humidity']) ? htmlspecialchars($_POST['humidity']) : '50' ?>);
</script>
</body>
</html>
