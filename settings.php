<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$username = $_SESSION['username'];
$role     = $_SESSION['role'];
$user_id  = $_SESSION['user_id'];
$success  = '';
$error    = '';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($_POST['action'] === 'update_profile') {
        $new_username = trim($_POST['new_username'] ?? '');
        $new_email    = trim($_POST['new_email'] ?? '');

        if (empty($new_username) || empty($new_email)) {
            $error = 'Username and email cannot be empty.';
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Check duplicate (exclude current user)
            $check = $db->prepare("SELECT user_id FROM users WHERE (username=? OR email=?) AND user_id != ?");
            $check->bind_param("ssi", $new_username, $new_email, $user_id);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $error = 'That username or email is already taken.';
            } else {
                $stmt = $db->prepare("UPDATE users SET username=?, email=? WHERE user_id=?");
                $stmt->bind_param("ssi", $new_username, $new_email, $user_id);
                $stmt->execute();
                $stmt->close();
                $_SESSION['username'] = $new_username;
                $username = $new_username;
                $success  = 'Profile updated successfully!';
            }
            $check->close();
        }
    }

    if ($_POST['action'] === 'change_password') {
        $current  = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        // Fetch current hash from DB
        $stmt = $db->prepare("SELECT password FROM users WHERE user_id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!password_verify($current, $row['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new_pass) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new_pass !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password=? WHERE user_id=?");
            $stmt->bind_param("si", $hash, $user_id);
            $stmt->execute();
            $stmt->close();
            $success = 'Password changed successfully!';
        }
    }

    if ($_POST['action'] === 'update_prefs') {
        $_SESSION['pref_theme'] = $_POST['theme']  ?? 'light';
        $_SESSION['pref_notif'] = isset($_POST['notifications']) ? 1 : 0;
        $_SESSION['pref_unit']  = $_POST['unit']   ?? 'percent';
        $success = 'Preferences saved successfully!';
    }
}

// Fetch fresh user data from DB
$stmt = $db->prepare("SELECT username, email, role, created_at FROM users WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total_logs = $db->query("SELECT COUNT(*) as c FROM user_logs WHERE user_id=$user_id")->fetch_assoc()['c'];
$db->close();

$pref_theme = $_SESSION['pref_theme'] ?? 'light';
$pref_notif = $_SESSION['pref_notif'] ?? 1;
$pref_unit  = $_SESSION['pref_unit']  ?? 'percent';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings — SuccuTrack</title>
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
  .user-avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--terracotta),#E8A87C);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#fff}
  .user-name{font-size:13px;font-weight:500;color:rgba(255,255,255,0.85)}
  .user-role{font-size:11px;color:rgba(255,255,255,0.4)}
  .btn-logout{display:block;width:100%;padding:9px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:rgba(255,255,255,0.5);font-size:13px;text-align:center;text-decoration:none;transition:all .2s}
  .btn-logout:hover{background:rgba(193,127,89,0.2);color:#E8A87C}
  .main{margin-left:var(--sidebar-w);flex:1}
  .topbar{background:var(--white);border-bottom:1px solid var(--border);padding:18px 36px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50}
  .page-title{font-family:'Playfair Display',serif;font-size:24px;font-weight:700}
  .content{padding:36px;max-width:860px}
  .alert{border-radius:10px;padding:14px 18px;font-size:14px;margin-bottom:24px;animation:slideIn .3s}
  @keyframes slideIn{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
  .alert-success{background:#E8F5E9;border:1px solid #A5D6A7;color:#2E7D32}
  .alert-error{background:#FFEBEE;border:1px solid #EF9A9A;color:#B71C1C}
  .profile-banner{background:linear-gradient(135deg,var(--sage-dark) 0%,var(--sage) 60%,var(--sage-light) 100%);border-radius:20px;padding:28px 32px;margin-bottom:28px;display:flex;align-items:center;gap:24px;position:relative;overflow:hidden;animation:fadeIn .5s both}
  .profile-banner::after{content:'🌵';position:absolute;font-size:140px;right:-10px;bottom:-20px;opacity:0.1;transform:rotate(-10deg)}
  .avatar-circle{width:70px;height:70px;border-radius:50%;background:rgba(255,255,255,0.2);border:3px solid rgba(255,255,255,0.4);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;color:#fff;flex-shrink:0}
  .profile-info .pname{font-family:'Playfair Display',serif;font-size:22px;font-weight:700;color:#fff}
  .profile-info .pmeta{font-size:13px;color:rgba(255,255,255,0.7);margin-top:4px}
  .profile-chips{display:flex;gap:8px;margin-top:10px;flex-wrap:wrap}
  .pchip{padding:4px 12px;border-radius:20px;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);font-size:12px;color:rgba(255,255,255,0.85)}
  @keyframes fadeIn{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
  .tabs{display:flex;gap:4px;background:var(--white);border-radius:12px;padding:6px;border:1px solid var(--border);margin-bottom:24px;width:fit-content;flex-wrap:wrap}
  .tab-btn{padding:9px 20px;border-radius:8px;border:none;background:none;font-family:'DM Sans',sans-serif;font-size:14px;color:var(--muted);cursor:pointer;transition:all .2s}
  .tab-btn:hover{background:var(--cream);color:var(--dark)}
  .tab-btn.active{background:linear-gradient(135deg,var(--sage-dark),var(--sage));color:#fff}
  .settings-card{background:var(--white);border-radius:20px;border:1px solid var(--border);overflow:hidden;margin-bottom:20px;animation:fadeIn .4s .1s both;display:none}
  .settings-card.visible{display:block}
  .card-header{padding:22px 28px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px}
  .card-header-icon{font-size:22px}
  .card-header-title{font-family:'Playfair Display',serif;font-size:18px;font-weight:700}
  .card-header-sub{font-size:13px;color:var(--muted);margin-top:2px}
  .card-body{padding:28px}
  .field{margin-bottom:20px}
  .field label{display:block;font-size:12px;font-weight:500;letter-spacing:1px;text-transform:uppercase;color:var(--muted);margin-bottom:8px}
  .field input,.field select{width:100%;padding:13px 16px;border:1.5px solid var(--sand);border-radius:10px;font-family:'DM Sans',sans-serif;font-size:15px;color:var(--dark);background:var(--cream);outline:none;transition:all .2s}
  .field input:focus,.field select:focus{border-color:var(--sage);background:#fff;box-shadow:0 0 0 4px rgba(125,155,118,0.1)}
  .field input[readonly]{background:var(--sand);color:var(--muted);cursor:not-allowed}
  .field-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
  .field-hint{font-size:12px;color:var(--muted);margin-top:6px}
  .strength-bar-wrap{height:4px;background:var(--sand);border-radius:4px;margin-top:8px;overflow:hidden}
  .strength-bar{height:100%;border-radius:4px;transition:width .3s,background .3s;width:0}
  .strength-label{font-size:11px;margin-top:4px}
  .toggle-row{display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--border)}
  .toggle-row:last-child{border-bottom:none}
  .toggle-info .toggle-title{font-size:14px;font-weight:500}
  .toggle-info .toggle-sub{font-size:12px;color:var(--muted);margin-top:2px}
  .toggle{position:relative;width:44px;height:24px;flex-shrink:0}
  .toggle input{opacity:0;width:0;height:0}
  .toggle-slider{position:absolute;inset:0;background:var(--sand);border-radius:24px;cursor:pointer;transition:.3s}
  .toggle-slider:before{content:'';position:absolute;width:18px;height:18px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 4px rgba(0,0,0,.2)}
  .toggle input:checked+.toggle-slider{background:var(--sage)}
  .toggle input:checked+.toggle-slider:before{transform:translateX(20px)}
  .theme-options{display:flex;gap:12px;margin-top:8px}
  .theme-opt{flex:1;border:2px solid var(--border);border-radius:12px;padding:14px;cursor:pointer;transition:all .2s;text-align:center}
  .theme-opt:hover{border-color:var(--sage-light)}
  .theme-opt.selected{border-color:var(--sage-dark);background:rgba(125,155,118,0.07)}
  .theme-opt input{display:none}
  .theme-preview{width:100%;height:36px;border-radius:6px;margin-bottom:8px}
  .theme-name{font-size:13px;font-weight:500}
  .danger-zone{border:1.5px solid #EF9A9A;border-radius:16px;padding:22px;background:#FFFAFA}
  .danger-title{font-size:15px;font-weight:600;color:#B71C1C;margin-bottom:6px}
  .danger-sub{font-size:13px;color:#C62828;margin-bottom:16px;line-height:1.5}
  .btn-danger{padding:10px 22px;background:#FFEBEE;border:1.5px solid #EF9A9A;border-radius:8px;color:#B71C1C;font-family:'DM Sans',sans-serif;font-size:14px;cursor:pointer;transition:all .2s;text-decoration:none;display:inline-block}
  .btn-danger:hover{background:#FFCDD2}
  .btn-save{padding:13px 28px;background:linear-gradient(135deg,var(--sage-dark),var(--sage));border:none;border-radius:10px;cursor:pointer;font-family:'DM Sans',sans-serif;font-size:15px;font-weight:500;color:#fff;transition:all .25s;box-shadow:0 4px 14px rgba(74,103,65,0.25)}
  .btn-save:hover{transform:translateY(-2px)}
  .btn-secondary{padding:13px 22px;background:transparent;border:1.5px solid var(--sand);border-radius:10px;font-family:'DM Sans',sans-serif;font-size:14px;color:var(--muted);cursor:pointer;transition:all .2s}
  .btn-secondary:hover{border-color:var(--sage);color:var(--sage-dark)}
  .btn-row{display:flex;gap:12px;margin-top:8px;flex-wrap:wrap}
  .info-table{width:100%;border-collapse:collapse}
  .info-table td{padding:12px 0;border-bottom:1px solid var(--border);font-size:14px;vertical-align:middle}
  .info-table tr:last-child td{border-bottom:none}
  .info-table td:first-child{color:var(--muted);font-size:12px;text-transform:uppercase;letter-spacing:.8px;width:40%}
  .info-table td:last-child{font-weight:500}
  .badge-role{padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
  .badge-admin{background:#FFF3E0;color:#E65100}
  .badge-user{background:#E8F5E9;color:#2E7D32}
  @media(max-width:900px){.sidebar{display:none}.main{margin-left:0}.field-row{grid-template-columns:1fr}.theme-options{flex-direction:column}.profile-banner{flex-direction:column;text-align:center}}
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
    <?php if ($role === 'admin'): ?>
    <div class="nav-label">Admin</div>
    <a class="nav-link" href="admin_dashboard.php"><span class="nav-icon">🛡️</span> Admin Panel</a>
    <a class="nav-link" href="manage_users.php"><span class="nav-icon">👥</span> Manage Users</a>
    <?php endif; ?>
    <div class="nav-label">Account</div>
    <a class="nav-link active" href="settings.php"><span class="nav-icon">⚙️</span> Settings</a>
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
    <div class="page-title">Settings</div>
    <span style="font-size:13px;color:var(--muted)">Manage your account</span>
  </div>
  <div class="content">

    <?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Profile Banner -->
    <div class="profile-banner">
      <div class="avatar-circle"><?= strtoupper(substr($username,0,1)) ?></div>
      <div class="profile-info">
        <div class="pname"><?= htmlspecialchars($user_data['username']) ?></div>
        <div class="pmeta"><?= htmlspecialchars($user_data['email']) ?></div>
        <div class="profile-chips">
          <span class="pchip">🌿 <?= ucfirst($user_data['role']) ?></span>
          <span class="pchip">📋 <?= $total_logs ?> Logs</span>
          <span class="pchip">📅 Since <?= date('M Y', strtotime($user_data['created_at'])) ?></span>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="tabs">
      <button class="tab-btn active" onclick="switchTab('profile',this)">👤 Profile</button>
      <button class="tab-btn" onclick="switchTab('password',this)">🔒 Password</button>
      <button class="tab-btn" onclick="switchTab('preferences',this)">🎨 Preferences</button>
      <button class="tab-btn" onclick="switchTab('account',this)">ℹ️ Account Info</button>
    </div>

    <!-- PROFILE TAB -->
    <div id="tab-profile" class="settings-card visible">
      <div class="card-header">
        <span class="card-header-icon">👤</span>
        <div><div class="card-header-title">Edit Profile</div><div class="card-header-sub">Updates are saved directly to the database</div></div>
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="update_profile">
          <div class="field-row">
            <div class="field">
              <label>Username</label>
              <input type="text" name="new_username" value="<?= htmlspecialchars($user_data['username']) ?>" required>
              <div class="field-hint">Your display name across the system.</div>
            </div>
            <div class="field">
              <label>Email Address</label>
              <input type="email" name="new_email" value="<?= htmlspecialchars($user_data['email']) ?>" required>
              <div class="field-hint">Must be a valid and unique email.</div>
            </div>
          </div>
          <div class="field">
            <label>Role</label>
            <input type="text" value="<?= ucfirst($role) ?>" readonly>
            <div class="field-hint">Assigned by an administrator and cannot be changed here.</div>
          </div>
          <div class="btn-row">
            <button type="submit" class="btn-save">💾 Save Profile</button>
            <button type="reset" class="btn-secondary">↺ Reset</button>
          </div>
        </form>
      </div>
    </div>

    <!-- PASSWORD TAB -->
    <div id="tab-password" class="settings-card">
      <div class="card-header">
        <span class="card-header-icon">🔒</span>
        <div><div class="card-header-title">Change Password</div><div class="card-header-sub">Passwords are securely hashed in the database</div></div>
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="change_password">
          <div class="field">
            <label>Current Password</label>
            <input type="password" name="current_password" placeholder="Enter your current password" required>
          </div>
          <div class="field-row">
            <div class="field">
              <label>New Password</label>
              <input type="password" name="new_password" id="newPassInput" placeholder="Min. 6 characters" required oninput="checkStrength(this.value)">
              <div class="strength-bar-wrap"><div class="strength-bar" id="strengthBar"></div></div>
              <div class="strength-label" id="strengthLabel" style="color:var(--muted)">Enter a new password</div>
            </div>
            <div class="field">
              <label>Confirm New Password</label>
              <input type="password" name="confirm_password" id="confirmPassInput" placeholder="Re-enter new password" required oninput="checkMatch()">
              <div class="field-hint" id="matchHint" style="color:var(--muted)">Must match the new password</div>
            </div>
          </div>
          <div style="background:var(--cream);border-radius:10px;padding:14px 16px;margin-bottom:20px;font-size:13px;color:var(--muted);line-height:1.6">
            💡 Use at least 8 characters with uppercase, numbers, and symbols for a strong password.
          </div>
          <div class="btn-row">
            <button type="submit" class="btn-save">🔒 Update Password</button>
          </div>
        </form>
      </div>
    </div>

    <!-- PREFERENCES TAB -->
    <div id="tab-preferences" class="settings-card">
      <div class="card-header">
        <span class="card-header-icon">🎨</span>
        <div><div class="card-header-title">Preferences</div><div class="card-header-sub">Customize your SuccuTrack experience</div></div>
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="update_prefs">
          <div class="field">
            <label>Display Theme</label>
            <div class="theme-options">
              <label class="theme-opt <?= $pref_theme==='light'?'selected':'' ?>" onclick="selectTheme(this)">
                <input type="radio" name="theme" value="light" <?= $pref_theme==='light'?'checked':'' ?>>
                <div class="theme-preview" style="background:linear-gradient(135deg,#F5F0E8,#FEFDF9)"></div>
                <div class="theme-name">☀️ Light</div>
              </label>
              <label class="theme-opt <?= $pref_theme==='dark'?'selected':'' ?>" onclick="selectTheme(this)">
                <input type="radio" name="theme" value="dark" <?= $pref_theme==='dark'?'checked':'' ?>>
                <div class="theme-preview" style="background:linear-gradient(135deg,#1C2B1A,#2D4A2A)"></div>
                <div class="theme-name">🌙 Dark</div>
              </label>
              <label class="theme-opt <?= $pref_theme==='nature'?'selected':'' ?>" onclick="selectTheme(this)">
                <input type="radio" name="theme" value="nature" <?= $pref_theme==='nature'?'checked':'' ?>>
                <div class="theme-preview" style="background:linear-gradient(135deg,#7D9B76,#A8C5A0)"></div>
                <div class="theme-name">🌿 Nature</div>
              </label>
            </div>
          </div>
          <div class="field" style="margin-top:8px">
            <label>Notifications & Display</label>
            <div class="toggle-row">
              <div class="toggle-info">
                <div class="toggle-title">Enable Notifications</div>
                <div class="toggle-sub">Alerts when humidity reaches critical levels</div>
              </div>
              <label class="toggle"><input type="checkbox" name="notifications" <?= $pref_notif?'checked':'' ?>><span class="toggle-slider"></span></label>
            </div>
            <div class="toggle-row">
              <div class="toggle-info">
                <div class="toggle-title">Show Humidity Bar on Logs</div>
                <div class="toggle-sub">Visual bar indicator in the logs table</div>
              </div>
              <label class="toggle"><input type="checkbox" name="show_bar" checked><span class="toggle-slider"></span></label>
            </div>
            <div class="toggle-row">
              <div class="toggle-info">
                <div class="toggle-title">Auto-scroll to Result</div>
                <div class="toggle-sub">Scroll to analysis result after submitting</div>
              </div>
              <label class="toggle"><input type="checkbox" name="auto_scroll" checked><span class="toggle-slider"></span></label>
            </div>
          </div>
          <div class="field">
            <label>Humidity Display Unit</label>
            <select name="unit">
              <option value="percent" <?= $pref_unit==='percent'?'selected':'' ?>>Percentage (%)</option>
              <option value="decimal" <?= $pref_unit==='decimal'?'selected':'' ?>>Decimal (0.00–1.00)</option>
            </select>
          </div>
          <div class="btn-row">
            <button type="submit" class="btn-save">💾 Save Preferences</button>
          </div>
        </form>
      </div>
    </div>

    <!-- ACCOUNT INFO TAB -->
    <div id="tab-account" class="settings-card">
      <div class="card-header">
        <span class="card-header-icon">ℹ️</span>
        <div><div class="card-header-title">Account Information</div><div class="card-header-sub">Read-only summary from the database</div></div>
      </div>
      <div class="card-body">
        <table class="info-table">
          <tr><td>User ID</td><td>#<?= $user_id ?></td></tr>
          <tr><td>Username</td><td><?= htmlspecialchars($user_data['username']) ?></td></tr>
          <tr><td>Email</td><td><?= htmlspecialchars($user_data['email']) ?></td></tr>
          <tr><td>Role</td><td><span class="badge-role badge-<?= $role ?>"><?= ucfirst($role) ?></span></td></tr>
          <tr><td>Member Since</td><td><?= date('F j, Y', strtotime($user_data['created_at'])) ?></td></tr>
          <tr><td>Total Logs</td><td><?= $total_logs ?> record(s) in database</td></tr>
          <tr><td>Session Started</td><td><?= date('F j, Y g:i A') ?></td></tr>
        </table>

        <div class="danger-zone" style="margin-top:28px">
          <div class="danger-title">⚠️ Danger Zone</div>
          <div class="danger-sub">Signing out will end your session. Deleting logs removes them permanently from the database.</div>
          <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a href="logout.php" class="btn-danger" onclick="return confirm('Sign out?')">🚪 Sign Out</a>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
  function switchTab(name, btn) {
    document.querySelectorAll('.settings-card').forEach(c => c.classList.remove('visible'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('visible');
    btn.classList.add('active');
  }
  function checkStrength(val) {
    const bar = document.getElementById('strengthBar');
    const lbl = document.getElementById('strengthLabel');
    let score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const levels = [
      {w:'0%',  c:'#ccc',    t:'Too short'},
      {w:'20%', c:'#e74c3c', t:'Very weak'},
      {w:'40%', c:'#e67e22', t:'Weak'},
      {w:'60%', c:'#f1c40f', t:'Fair'},
      {w:'80%', c:'#27ae60', t:'Strong'},
      {w:'100%',c:'#1a6e3c', t:'Very strong'},
    ];
    const lv = levels[score] || levels[0];
    bar.style.width = lv.w; bar.style.background = lv.c;
    lbl.textContent = lv.t; lbl.style.color = lv.c;
  }
  function checkMatch() {
    const np = document.getElementById('newPassInput').value;
    const cp = document.getElementById('confirmPassInput').value;
    const hint = document.getElementById('matchHint');
    if (!cp) { hint.textContent='Must match the new password'; hint.style.color='var(--muted)'; return; }
    if (np===cp) { hint.textContent='✅ Passwords match'; hint.style.color='#27ae60'; }
    else         { hint.textContent='❌ Passwords do not match'; hint.style.color='#e74c3c'; }
  }
  function selectTheme(el) {
    document.querySelectorAll('.theme-opt').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
  }
</script>
</body>
</html>
