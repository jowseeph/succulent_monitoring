<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php"); exit();
}

$msg   = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();

    if ($_POST['action'] === 'create') {
        $uname = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $role  = $_POST['role'] ?? 'user';

        if (empty($uname) || empty($email) || empty($pass)) {
            $error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (strlen($pass) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            // Check for duplicate username or email
            $check = $db->prepare("SELECT user_id FROM users WHERE username=? OR email=?");
            $check->bind_param("ss", $uname, $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $error = 'Username or email already exists.';
            } else {
                $hashed = password_hash($pass, PASSWORD_DEFAULT);
                $stmt   = $db->prepare("INSERT INTO users (username, email, password, role) VALUES (?,?,?,?)");
                $stmt->bind_param("ssss", $uname, $email, $hashed, $role);
                $stmt->execute();
                $msg = '✅ User "' . htmlspecialchars($uname) . '" created successfully!';
                $stmt->close();
            }
            $check->close();
        }
    }

    if ($_POST['action'] === 'delete') {
        $uid = intval($_POST['uid']);
        // Prevent deleting self or main admin
        if ($uid === $_SESSION['user_id']) {
            $error = 'You cannot delete your own account.';
        } else {
            $stmt = $db->prepare("DELETE FROM users WHERE user_id=?");
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $msg = '🗑️ User deleted successfully.';
            $stmt->close();
        }
    }

    $db->close();
}

// Fetch fresh user list
$db    = getDB();
$users = $db->query("SELECT user_id, username, email, role, created_at FROM users ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users — SuccuTrack</title>
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
  .topbar{background:var(--white);border-bottom:1px solid var(--border);padding:18px 36px;display:flex;align-items:center;position:sticky;top:0;z-index:50}
  .page-title{font-family:'Playfair Display',serif;font-size:24px;font-weight:700}
  .content{padding:36px;max-width:1000px}
  .alert{border-radius:10px;padding:14px 18px;font-size:14px;margin-bottom:20px;animation:fadeIn .3s}
  .alert-success{background:#E8F5E9;border:1px solid #A5D6A7;color:#2E7D32}
  .alert-error{background:#FFEBEE;border:1px solid #EF9A9A;color:#B71C1C}
  @keyframes fadeIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
  .card{background:var(--white);border-radius:20px;padding:28px;border:1px solid var(--border);margin-bottom:24px;animation:fadeIn .5s both}
  .card:nth-child(3){animation-delay:.15s}
  .card-title{font-family:'Playfair Display',serif;font-size:20px;font-weight:700;margin-bottom:4px}
  .card-sub{font-size:13px;color:var(--muted);margin-bottom:20px}
  .field{margin-bottom:16px}
  .field label{display:block;font-size:11px;font-weight:500;letter-spacing:1px;text-transform:uppercase;color:var(--muted);margin-bottom:7px}
  .field input,.field select{width:100%;padding:12px 14px;border:1.5px solid var(--sand);border-radius:10px;font-family:'DM Sans',sans-serif;font-size:14px;color:var(--dark);background:var(--cream);outline:none;transition:border-color .2s}
  .field input:focus,.field select:focus{border-color:var(--sage);background:#fff}
  .field-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
  .btn-green{padding:11px 22px;background:linear-gradient(135deg,var(--sage-dark),var(--sage));border:none;border-radius:8px;cursor:pointer;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:500;color:#fff;transition:all .2s}
  .btn-green:hover{transform:translateY(-1px)}
  .btn-red{padding:7px 14px;background:#FFEBEE;border:1px solid #EF9A9A;border-radius:8px;cursor:pointer;font-family:'DM Sans',sans-serif;font-size:12px;color:#B71C1C;transition:all .2s}
  .btn-red:hover{background:#FFCDD2}
  .log-table-wrap{border-radius:12px;border:1px solid var(--border);overflow:auto}
  table{width:100%;border-collapse:collapse}
  thead tr{background:var(--cream)}
  th{padding:11px 14px;text-align:left;font-size:11px;letter-spacing:1px;text-transform:uppercase;color:var(--muted);font-weight:500}
  td{padding:12px 14px;font-size:14px;border-top:1px solid var(--border);vertical-align:middle}
  tr:hover td{background:rgba(125,155,118,0.04)}
  .badge-role-user{padding:3px 10px;border-radius:20px;font-size:11px;background:#E8F5E9;color:#2E7D32;font-weight:500}
  .badge-role-admin{padding:3px 10px;border-radius:20px;font-size:11px;background:#FFF3E0;color:#E65100;font-weight:500}
  @media(max-width:800px){.sidebar{display:none}.main{margin-left:0}.field-row{grid-template-columns:1fr}}
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
    <a class="nav-link" href="admin_dashboard.php"><span class="nav-icon">🛡️</span> Admin Panel</a>
    <a class="nav-link active" href="manage_users.php"><span class="nav-icon">👥</span> Manage Users</a>
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
  <div class="topbar"><div class="page-title">Manage Users</div></div>
  <div class="content">

    <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Create User Form -->
    <div class="card">
      <div class="card-title">Create New User</div>
      <div class="card-sub">Add a new account directly to the database</div>
      <form method="POST">
        <input type="hidden" name="action" value="create">
        <div class="field-row">
          <div class="field"><label>Username</label><input type="text" name="username" placeholder="e.g. jdelacruz" required></div>
          <div class="field"><label>Email</label><input type="email" name="email" placeholder="email@example.com" required></div>
        </div>
        <div class="field-row">
          <div class="field"><label>Password</label><input type="password" name="password" placeholder="Min. 6 characters" required></div>
          <div class="field"><label>Role</label>
            <select name="role">
              <option value="user">User</option>
              <option value="admin">Admin</option>
            </select>
          </div>
        </div>
        <button type="submit" class="btn-green">➕ Create User</button>
      </form>
    </div>

    <!-- Users Table -->
    <div class="card">
      <div class="card-title">All Users</div>
      <div class="card-sub"><?= count($users) ?> account(s) registered in the database</div>
      <div class="log-table-wrap">
        <table>
          <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Joined</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
              <td style="color:var(--muted)">#<?= $u['user_id'] ?></td>
              <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
              <td style="color:var(--muted);font-size:13px"><?= htmlspecialchars($u['email']) ?></td>
              <td><span class="badge-role-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
              <td style="font-size:12px;color:var(--muted)"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
              <td>
                <?php if ($u['user_id'] !== $_SESSION['user_id']): ?>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete user <?= htmlspecialchars($u['username']) ?>? This cannot be undone.')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="uid" value="<?= $u['user_id'] ?>">
                  <button type="submit" class="btn-red">🗑️ Delete</button>
                </form>
                <?php else: ?>
                <span style="font-size:12px;color:var(--muted)">You</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</body>
</html>
