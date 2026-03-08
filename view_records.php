<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }

$user_id      = $_SESSION['user_id'];
$succulent_id = isset($_GET['succulent_id']) ? (int)$_GET['succulent_id'] : 0;

$succulent = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM succulents WHERE id='$succulent_id' AND user_id='$user_id'"));

if (!$succulent) { header("Location: dashboard.php"); exit; }

$records = mysqli_query($conn,
    "SELECT * FROM monitoring_records WHERE succulent_id='$succulent_id' ORDER BY record_date DESC");

$condition_icons = ['healthy'=>'✅','wilting'=>'😟','yellowing'=>'🟡','rotting'=>'🔴','shriveling'=>'🍂'];
$water_icons     = ['watered'=>'💧','skipped'=>'⏭️','overdue'=>'⚠️'];
$moisture_icons  = ['dry'=>'🏜️','moist'=>'🌱','wet'=>'💦'];
$sun_icons       = ['full_sun'=>'☀️','partial_shade'=>'🌤️','shade'=>'🌥️'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Records – <?= htmlspecialchars($succulent['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root { --sage:#7a9e7e; --moss:#4a6c50; --cream:#f5f0e8; --sand:#d4c5a9; --dark:#2b3a2e; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif; background:var(--cream); color:var(--dark); }
        nav { background:var(--dark); padding:14px 32px; display:flex; align-items:center; justify-content:space-between; }
        .nav-brand { display:flex; align-items:center; gap:10px; color:#fff; text-decoration:none; }
        .nav-brand span { font-size:1.5rem; }
        .nav-brand h2 { font-family:'Playfair Display',serif; font-size:1.2rem; }
        .nav-links a { color:rgba(255,255,255,.75); text-decoration:none; margin-left:20px; font-size:.9rem; }
        .nav-links a:hover { color:#fff; }
        .container { max-width:1000px; margin:36px auto; padding:0 24px; }
        .plant-header { background:#fff; border-radius:20px; padding:28px 32px; margin-bottom:24px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 2px 12px rgba(43,58,46,.08); }
        .plant-title h1 { font-family:'Playfair Display',serif; font-size:1.7rem; }
        .plant-title p { color:var(--sage); font-size:.9rem; margin-top:4px; }
        .btn { padding:10px 20px; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:.9rem; font-weight:500; cursor:pointer; border:none; text-decoration:none; display:inline-block; transition:all .2s; }
        .btn-primary { background:var(--moss); color:#fff; }
        .btn-primary:hover { background:var(--dark); }
        .btn-sm { padding:7px 14px; font-size:.82rem; }
        .btn-danger { background:#e57373; color:#fff; }
        .btn-danger:hover { background:#c62828; }
        .section-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
        .section-header h2 { font-family:'Playfair Display',serif; font-size:1.2rem; }
        table { width:100%; border-collapse:collapse; background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 2px 12px rgba(43,58,46,.07); }
        thead { background:var(--dark); color:#fff; }
        th { padding:13px 16px; text-align:left; font-size:.83rem; font-weight:500; }
        td { padding:12px 16px; border-bottom:1px solid #f0ece4; font-size:.88rem; }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:#faf7f2; }
        .badge { padding:3px 10px; border-radius:20px; font-size:.75rem; font-weight:500; display:inline-block; }
        .badge-healthy { background:#e6f4ea; color:#2e7d32; }
        .badge-wilting { background:#fff3e0; color:#e65100; }
        .badge-yellowing { background:#fffde7; color:#f57f17; }
        .badge-rotting { background:#fde8e8; color:#b71c1c; }
        .badge-shriveling { background:#fce4ec; color:#880e4f; }
        .empty-state { text-align:center; padding:48px; color:#aaa; background:#fff; border-radius:16px; }
        .back-link { display:inline-block; margin-bottom:20px; color:var(--moss); text-decoration:none; font-size:.9rem; }
    </style>
</head>
<body>
<nav>
    <a class="nav-brand" href="dashboard.php"><span>🌵</span><h2>Succulent Monitor</h2></a>
    <div class="nav-links">
        <a href="dashboard.php">My Succulents</a>
        <a href="add_succulent.php">Add Succulent</a>
        <a href="add_record.php">Log Monitoring</a>
        <a href="logout.php">Logout</a>
    </div>
</nav>
<div class="container">
    <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    <div class="plant-header">
        <div class="plant-title">
            <h1>🌵 <?= htmlspecialchars($succulent['name']) ?></h1>
            <p><?= htmlspecialchars($succulent['species'] ?: 'Unknown species') ?> &nbsp;|&nbsp; <?= htmlspecialchars($succulent['location'] ?: 'No location set') ?></p>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            <a href="add_record.php?succulent_id=<?= $succulent['id'] ?>" class="btn btn-primary">+ Log Today</a>
            <a href="delete_succulent.php?id=<?= $succulent['id'] ?>" class="btn btn-sm btn-danger"
               onclick="return confirm('Delete this succulent and ALL its records permanently?')">Delete Plant</a>
        </div>
    </div>
    <div class="section-header">
        <h2>Monitoring History</h2>
        <span style="color:#aaa;font-size:.85rem;"><?= mysqli_num_rows($records) ?> record(s)</span>
    </div>
    <?php if (mysqli_num_rows($records) === 0): ?>
    <div class="empty-state">
        <p>📋 No records yet. Log your first monitoring check!</p>
        <br><a href="add_record.php?succulent_id=<?= $succulent['id'] ?>" class="btn btn-primary">Log Now</a>
    </div>
    <?php else: ?>
    <table>
        <thead>
            <tr><th>Date</th><th>Leaf Condition</th><th>Watering</th><th>Soil</th><th>Sunlight</th><th>Height (cm)</th><th>Notes</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php while ($rec = mysqli_fetch_assoc($records)): ?>
            <tr>
                <td><?= date('M d, Y', strtotime($rec['record_date'])) ?></td>
                <td><span class="badge badge-<?= $rec['leaf_condition'] ?>"><?= $condition_icons[$rec['leaf_condition']] ?> <?= ucfirst($rec['leaf_condition']) ?></span></td>
                <td><?= $water_icons[$rec['watering_status']] ?> <?= ucfirst($rec['watering_status']) ?></td>
                <td><?= $moisture_icons[$rec['soil_moisture']] ?> <?= ucfirst($rec['soil_moisture']) ?></td>
                <td><?= $sun_icons[$rec['sunlight_exposure']] ?> <?= ucwords(str_replace('_',' ',$rec['sunlight_exposure'])) ?></td>
                <td><?= $rec['height_cm'] !== null ? $rec['height_cm'].' cm' : '—' ?></td>
                <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($rec['notes'] ?: '—') ?></td>
                <td><a href="delete_record.php?id=<?= $rec['id'] ?>&succulent_id=<?= $succulent_id ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this record?')">Delete</a></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
</body>
</html>