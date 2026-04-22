<?php
require_once 'config.php';

$db = getDB();
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Get all machines with completion stats
$machines = $db->query("SELECT * FROM machines ORDER BY name")->fetchAll();

$stats = [];
foreach ($machines as $m) {
    $mid = $m['id'];
    // Count all tasks
    $total_tasks = $db->query("SELECT COUNT(*) FROM maintenance_tasks")->fetchColumn();
    // Count completed records for this machine/year
    $completed = $db->prepare("SELECT COUNT(*) FROM maintenance_records WHERE machine_id=? AND year=? AND completed=1");
    $completed->execute([$mid, $year]);
    $done = $completed->fetchColumn();
    
    // Count total expected records
    $monthly_tasks = $db->query("SELECT COUNT(*) FROM maintenance_tasks WHERE maintenance_type='monthly'")->fetchColumn();
    $quarterly_tasks = $db->query("SELECT COUNT(*) FROM maintenance_tasks WHERE maintenance_type='quarterly'")->fetchColumn();
    $semi_tasks = $db->query("SELECT COUNT(*) FROM maintenance_tasks WHERE maintenance_type='semi_annual'")->fetchColumn();
    $annual_tasks = $db->query("SELECT COUNT(*) FROM maintenance_tasks WHERE maintenance_type='annual'")->fetchColumn();
    
    $total_expected = ($monthly_tasks * 12) + ($quarterly_tasks * 4) + ($semi_tasks * 2) + ($annual_tasks * 1);
    
    $stats[$mid] = [
        'completed' => $done,
        'total' => $total_expected,
        'pct' => $total_expected > 0 ? round(($done / $total_expected) * 100) : 0
    ];
}

$years = range(date('Y') , date('Y') + 3);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Viscom Maintenance Tracking System</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="app-wrapper">
  <header class="app-header">
    <div class="header-inner">
      <div class="logo">
        <span class="logo-icon">⚙</span>
        <div>
          <div class="logo-title">Viscom Maintenance</div>
          <div class="logo-sub">Tracking System v2.0</div>
        </div>
      </div>
      <div class="header-right">
        <form method="get" class="year-form">
          <label>Year:</label>
          <select name="year" onchange="this.form.submit()">
            <?php foreach($years as $y): ?>
            <option value="<?=$y?>" <?=$y==$year?'selected':''?>><?=$y?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>
    </div>
  </header>

  <main class="main-content">
    <div class="page-title">
      <h1>Machine Overview — <?=$year?></h1>
      <p>Select a machine to view and manage its maintenance records.</p>
    </div>

    <div class="machine-grid">
      <?php foreach ($machines as $m): 
        $mid = $m['id'];
        $pct = $stats[$mid]['pct'];
        $color = $pct >= 80 ? 'green' : ($pct >= 40 ? 'orange' : 'red');
      ?>
      <a href="machine.php?machine_id=<?=$mid?>&year=<?=$year?>" class="machine-card">
        <div class="machine-icon">🔬</div>
        <div class="machine-name"><?=htmlspecialchars($m['name'])?></div>
        <div class="machine-meta">
          <span><?=htmlspecialchars($m['serial_number'])?></span>
          <span><?=htmlspecialchars($m['location'])?></span>
        </div>
        <div class="progress-bar-wrap">
          <div class="progress-bar <?=$color?>" style="width:<?=$pct?>%"></div>
        </div>
        <div class="progress-label"><?=$stats[$mid]['completed']?> / <?=$stats[$mid]['total']?> tasks (<?=$pct?>%)</div>
        <div class="card-action">View Maintenance →</div>
      </a>
      <?php endforeach; ?>
    </div>

    <div class="quick-actions">
      <a href="print_all.php?year=<?=$year?>" class="btn btn-outline" target="_blank">🖨 Print All Machines (<?=$year?>)</a>
      <a href="settings.php" class="btn btn-outline">⚙ Settings / Machines</a>
    </div>
  </main>
</div>
</body>
</html>
