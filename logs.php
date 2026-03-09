<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$username = $_SESSION['username'];
$role     = $_SESSION['role'];
$user_id  = $_SESSION['user_id'];
$filter   = $_GET['filter'] ?? 'all';

$db = getDB();

// Admins see all logs with username; users see only their own
if ($role === 'admin') {
    $sql = "SELECT u.username, h.humidity_percent, h.status, h.recorded_at
            FROM user_logs ul
            JOIN humidity h ON ul.humidity_id = h.humidity_id
            JOIN users u    ON ul.user_id = u.user_id
            ORDER BY h.recorded_at DESC";
    $logs = $db->query($sql)->fetch_all(MYSQLI_ASSOC);
} else {
    $stmt = $db->prepare(
        "SELECT u.username, h.humidity_percent, h.status, h.recorded_at
         FROM user_logs ul
         JOIN humidity h ON ul.humidity_id = h.humidity_id
         JOIN users u    ON ul.user_id = u.user_id
         WHERE ul.user_id = ?
         ORDER BY h.recorded_at DESC"
    );
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$db->close();

// Filter
$filtered = $logs;
if ($filter !== 'all') {
    $filtered = array_values(array_filter($logs, function($l) use ($filter) {
        return strtolower(str_replace(' ', '-', $l['status'])) === $filter;
    }));
}

// Count per status
$counts = ['all' => count($logs)];
foreach ($logs as $l) {
    $key = strtolower(str_replace(' ', '-', $l['status']));
    $counts[$key] = ($counts[$key] ?? 0) + 1;
}

function statusMeta($status) {
    return match(true) {
        str_contains($status,'Critically Dry')   => ['icon'=>'🏜️','pill'=>'pill-cd','color'=>'#C0392B'],
        str_contains($status,'Dry')              => ['icon'=>'☀️','pill'=>'pill-d', 'color'=>'#E67E22'],
        str_contains($status,'Ideal')            => ['icon'=>'✅','pill'=>'pill-i', 'color'=>'#27AE60'],
        str_contains($status,'Critically Humid') => ['icon'=>'🌊','pill'=>'pill-ch','color'=>'#8E44AD'],
        str_contains($status,'Humid')            => ['icon'=>'💦','pill'=>'pill-h', 'color'=>'#2980B9'],
        default                                  => ['icon'=>'❓','pill'=>'',       'color'=>'#999'],
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>All Logs — SuccuTrack</title>
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
  .topbar-actions{display:flex;gap:10px}
  .btn-add{padding:8px 16px;background:linear-gradient(135deg,var(--sage-dark),var(--sage));border:none;border-radius:8px;color:#fff;font-size:13px;font-family:'DM Sans',sans-serif;cursor:pointer;text-decoration:none;transition:all .2s}
  .content{padding:36px}
  .summary-row{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:28px}
  .sum-card{flex:1;min-width:120px;background:var(--white);border-radius:14px;padding:16px 18px;border:1px solid var(--border);cursor:pointer;text-decoration:none;transition:all .2s;display:block}
  .sum-card:hover,.sum-card.active{transform:translateY(-2px);box-shadow:0 6px 20px rgba(28,43,26,0.1)}
  .sum-card.active{border-color:var(--sage-dark);border-width:2px}
  .sum-icon{font-size:20px;margin-bottom:6px}
  .sum-label{font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--muted)}
  .sum-count{font-size:24px;font-weight:700;font-family:'Playfair Display',serif;color:var(--dark)}
  @keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
  .card{background:var(--white);border-radius:20px;border:1px solid var(--border);overflow:hidden;animation:fadeIn .4s .1s both}
  .card-header{padding:22px 28px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
  .card-title{font-family:'Playfair Display',serif;font-size:18px;font-weight:700}
  .card-sub{font-size:13px;color:var(--muted);margin-top:2px}
  .record-count{font-size:12px;color:var(--muted);background:var(--cream);padding:4px 12px;border-radius:20px}
  table{width:100%;border-collapse:collapse}
  thead tr{background:var(--cream)}
  th{padding:13px 20px;text-align:left;font-size:11px;letter-spacing:1px;text-transform:uppercase;color:var(--muted);font-weight:500;white-space:nowrap}
  td{padding:14px 20px;font-size:14px;border-top:1px solid var(--border);vertical-align:middle}
  tr:hover td{background:rgba(125,155,118,0.035)}
  .empty-state{text-align:center;padding:60px 20px;color:var(--muted)}
  .empty-icon{font-size:52px;margin-bottom:12px;opacity:.35}
  .empty-title{font-size:16px;font-weight:500;color:var(--dark);margin-bottom:6px}
  .empty-link{display:inline-block;margin-top:16px;padding:10px 22px;background:linear-gradient(135deg,var(--sage-dark),var(--sage));color:#fff;border-radius:8px;text-decoration:none;font-size:14px}
  .pill{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:500}
  .pill-i{background:#E8F5E9;color:#2E7D32}
  .pill-d{background:#FFF3E0;color:#E65100}
  .pill-cd{background:#FFEBEE;color:#B71C1C}
  .pill-h{background:#E3F2FD;color:#1565C0}
  .pill-ch{background:#F3E5F5;color:#6A1B9A}
  .hbar-wrap{width:80px;height:6px;background:var(--sand);border-radius:4px;overflow:hidden;display:inline-block;vertical-align:middle}
  .hbar{height:100%;border-radius:4px}
  .admin-badge{font-size:10px;background:rgba(255,255,255,0.1);padding:2px 6px;border-radius:6px;color:rgba(255,255,255,0.5);margin-left:4px}
  @media(max-width:900px){.sidebar{display:none}.main{margin-left:0}}
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
    <a class="nav-link active" href="logs.php"><span class="nav-icon">📋</span> All Logs</a>
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
    <div class="page-title">All Logs <?php if ($role==='admin'): ?><span style="font-size:13px;font-family:'DM Sans',sans-serif;color:var(--muted);font-weight:400">(All Users)</span><?php endif; ?></div>
    <div class="topbar-actions">
      <a href="add_record.php" class="btn-add">➕ Add Record</a>
    </div>
  </div>

  <div class="content">
    <!-- Filter Summary Cards -->
    <div class="summary-row">
      <?php
      $filters = [
        'all'              => ['icon'=>'📋','label'=>'All'],
        'ideal'            => ['icon'=>'✅','label'=>'Ideal'],
        'dry'              => ['icon'=>'☀️','label'=>'Dry'],
        'critically-dry'   => ['icon'=>'🏜️','label'=>'Crit. Dry'],
        'humid'            => ['icon'=>'💦','label'=>'Humid'],
        'critically-humid' => ['icon'=>'🌊','label'=>'Crit. Humid'],
      ];
      foreach ($filters as $key => $f): $isActive = $filter===$key; ?>
      <a href="logs.php?filter=<?=$key?>" class="sum-card <?=$isActive?'active':''?>">
        <div class="sum-icon"><?=$f['icon']?></div>
        <div class="sum-label"><?=$f['label']?></div>
        <div class="sum-count"><?=$counts[$key]??0?></div>
      </a>
      <?php endforeach; ?>
    </div>

    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">Humidity Records</div>
          <div class="card-sub">
            <?php if ($filter==='all'): ?>
              <?= $role==='admin' ? 'All users — ' : 'Your records — ' ?><?= count($logs) ?> total
            <?php else: ?>
              Filtered: <strong><?= ucwords(str_replace('-',' ',$filter)) ?></strong> — <?= count($filtered) ?> record(s)
            <?php endif; ?>
          </div>
        </div>
        <span class="record-count"><?= count($filtered) ?> entries</span>
      </div>

      <?php if (empty($filtered)): ?>
      <div class="empty-state">
        <div class="empty-icon">🌵</div>
        <div class="empty-title"><?= empty($logs) ? 'No records in database yet' : 'No records match this filter' ?></div>
        <div style="font-size:13px"><?= empty($logs) ? 'Start by adding a humidity reading.' : 'Try a different category above.' ?></div>
        <?php if (empty($logs)): ?>
        <a href="add_record.php" class="empty-link">➕ Add First Record</a>
        <?php endif; ?>
      </div>
      <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <?php if ($role==='admin'): ?><th>User</th><?php endif; ?>
            <th>Humidity</th>
            <th>Visual</th>
            <th>Status</th>
            <th>Time</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($filtered as $i => $log):
            $meta = statusMeta($log['status']); ?>
          <tr>
            <td style="color:var(--muted);font-size:13px"><?= $i+1 ?></td>
            <?php if ($role==='admin'): ?>
            <td style="font-weight:500"><?= htmlspecialchars($log['username']) ?></td>
            <?php endif; ?>
            <td><strong style="font-size:16px"><?= number_format($log['humidity_percent'],1) ?></strong><span style="font-size:12px;color:var(--muted)">%</span></td>
            <td>
              <div class="hbar-wrap">
                <div class="hbar" style="width:<?= min($log['humidity_percent'],100) ?>%;background:<?= $meta['color'] ?>"></div>
              </div>
            </td>
            <td><span class="pill <?= $meta['pill'] ?>"><?= $meta['icon'] ?> <?= htmlspecialchars($log['status']) ?></span></td>
            <td style="color:var(--muted);font-size:13px"><?= date('g:i A', strtotime($log['recorded_at'])) ?></td>
            <td style="color:var(--muted);font-size:13px"><?= date('M j, Y', strtotime($log['recorded_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
