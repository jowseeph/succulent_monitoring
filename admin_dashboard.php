<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php"); exit();
}

$db = getDB();

// Real stats from DB
$users     = $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$all_logs  = $db->query("
    SELECT ul.log_id, u.username, h.humidity_percent, h.status, h.recorded_at
    FROM user_logs ul
    JOIN humidity h ON ul.humidity_id = h.humidity_id
    JOIN users u    ON ul.user_id = u.user_id
    ORDER BY h.recorded_at DESC
    LIMIT 50
")->fetch_all(MYSQLI_ASSOC);

$total_users  = count($users);
$total_logs   = $db->query("SELECT COUNT(*) as c FROM user_logs")->fetch_assoc()['c'];
$ideal_count  = $db->query("SELECT COUNT(*) as c FROM humidity WHERE status='Ideal'")->fetch_assoc()['c'];
$alert_count  = $db->query("SELECT COUNT(*) as c FROM humidity WHERE status LIKE 'Critically%'")->fetch_assoc()['c'];

$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — SuccuTrack</title>
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
  .badge-admin{padding:4px 12px;border-radius:20px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:1px;background:linear-gradient(135deg,var(--terracotta),#E8A87C);color:#fff}
  .content{padding:36px}
  .stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px}
  .stat-card{background:var(--white);border-radius:16px;padding:20px 22px;border:1px solid var(--border);animation:fadeIn .5s both;transition:transform .2s}
  .stat-card:hover{transform:translateY(-2px)}
  .stat-card:nth-child(2){animation-delay:.08s}.stat-card:nth-child(3){animation-delay:.14s}.stat-card:nth-child(4){animation-delay:.2s}
  @keyframes fadeIn{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
  .stat-icon{font-size:22px;margin-bottom:8px}
  .stat-label{font-size:11px;letter-spacing:1px;text-transform:uppercase;color:var(--muted)}
  .stat-value{font-size:28px;font-weight:600;font-family:'Playfair Display',serif;margin-top:4px}
  .stat-sub{font-size:12px;color:var(--muted)}
  .card{background:var(--white);border-radius:20px;padding:28px;border:1px solid var(--border);margin-bottom:24px;animation:fadeIn .5s .15s both}
  .card-title{font-family:'Playfair Display',serif;font-size:20px;font-weight:700;margin-bottom:4px}
  .card-sub{font-size:13px;color:var(--muted);margin-bottom:20px}
  .log-table-wrap{border-radius:12px;border:1px solid var(--border);overflow:auto}
  table{width:100%;border-collapse:collapse}
  thead tr{background:var(--cream)}
  th{padding:12px 16px;text-align:left;font-size:11px;letter-spacing:1px;text-transform:uppercase;color:var(--muted);font-weight:500}
  td{padding:13px 16px;font-size:14px;border-top:1px solid var(--border);vertical-align:middle}
  tr:hover td{background:rgba(125,155,118,0.04)}
  .badge-role-user{padding:3px 10px;border-radius:20px;font-size:11px;background:#E8F5E9;color:#2E7D32;font-weight:500}
  .badge-role-admin{padding:3px 10px;border-radius:20px;font-size:11px;background:#FFF3E0;color:#E65100;font-weight:500}
  .status-pill{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:500}
  .pill-Ideal{background:#E8F5E9;color:#2E7D32}.pill-Dry{background:#FFF3E0;color:#E65100}
  .pill-Humid{background:#E3F2FD;color:#1565C0}.pill-Critically-Dry{background:#FFEBEE;color:#B71C1C}
  .pill-Critically-Humid{background:#F3E5F5;color:#6A1B9A}
  @media(max-width:900px){.sidebar{display:none}.main{margin-left:0}.stats-row{grid-template-columns:1fr 1fr}}
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
    <a class="nav-link" href="add_record.php"><span class="nav-icon">➕</span> Add Record</a>
    <a class="nav-link" href="logs.php"><span class="nav-icon">📋</span> All Logs</a>
    <div class="nav-label">Admin</div>
    <a class="nav-link active" href="admin_dashboard.php"><span class="nav-icon">🛡️</span> Admin Panel</a>
    <a class="nav-link" href="manage_users.php"><span class="nav-icon">👥</span> Manage Users</a>
    <div class="nav-label">Account</div>
    <a class="nav-link" href="settings.php"><span class="nav-icon">⚙️</span> Settings</a>
  </nav>
  <div class="sidebar-user">
    <div class="user-info">
      <div class="user-avatar">A</div>
      <div><div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div><div class="user-role">Administrator</div></div>
    </div>
    <a class="btn-logout" href="logout.php">Sign Out</a>
  </div>
</aside>

<div class="main">
  <div class="topbar">
    <div class="page-title">Admin Dashboard</div>
    <span class="badge-admin">🛡️ Admin</span>
  </div>
  <div class="content">
    <div class="stats-row">
      <div class="stat-card"><div class="stat-icon">👥</div><div class="stat-label">Total Users</div><div class="stat-value"><?= $total_users ?></div><div class="stat-sub">Registered accounts</div></div>
      <div class="stat-card"><div class="stat-icon">📋</div><div class="stat-label">Total Logs</div><div class="stat-value"><?= $total_logs ?></div><div class="stat-sub">All humidity records</div></div>
      <div class="stat-card"><div class="stat-icon">✅</div><div class="stat-label">Ideal Readings</div><div class="stat-value"><?= $ideal_count ?></div><div class="stat-sub">In optimal range</div></div>
      <div class="stat-card"><div class="stat-icon">⚠️</div><div class="stat-label">Alerts</div><div class="stat-value"><?= $alert_count ?></div><div class="stat-sub">Critical conditions</div></div>
    </div>

    <div class="card">
      <div class="card-title">Registered Users</div>
      <div class="card-sub"><?= $total_users ?> account(s) in the system</div>
      <div class="log-table-wrap">
        <table>
          <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Joined</th></tr></thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
              <td style="color:var(--muted)">#<?= $u['user_id'] ?></td>
              <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
              <td style="color:var(--muted)"><?= htmlspecialchars($u['email']) ?></td>
              <td><span class="badge-role-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
              <td style="font-size:12px;color:var(--muted)"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-title">All Humidity Logs</div>
      <div class="card-sub">Latest 50 records across all users</div>
      <div class="log-table-wrap">
        <table>
          <thead><tr><th>Log ID</th><th>User</th><th>Humidity</th><th>Status</th><th>Recorded At</th></tr></thead>
          <tbody>
            <?php if (empty($all_logs)): ?>
            <tr><td colspan="5" style="text-align:center;color:var(--muted);padding:28px;font-style:italic">No logs recorded yet</td></tr>
            <?php else: foreach ($all_logs as $log):
              $pc = 'pill-' . str_replace(' ', '-', $log['status']); ?>
            <tr>
              <td style="color:var(--muted)">#<?= $log['log_id'] ?></td>
              <td><strong><?= htmlspecialchars($log['username']) ?></strong></td>
              <td><strong><?= number_format($log['humidity_percent'],1) ?>%</strong></td>
              <td><span class="status-pill <?= $pc ?>"><?= htmlspecialchars($log['status']) ?></span></td>
              <td style="font-size:12px;color:var(--muted)"><?= date('M j, Y g:i A', strtotime($log['recorded_at'])) ?></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</body>
</html>
