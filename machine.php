<?php
require_once 'config.php';

$db = getDB();
$machine_id = isset($_GET['machine_id']) ? (int)$_GET['machine_id'] : 0;
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'monthly';

if (!$machine_id) { header('Location: index.php'); exit; }

$machine = $db->prepare("SELECT * FROM machines WHERE id=?");
$machine->execute([$machine_id]);
$machine = $machine->fetch();
if (!$machine) { header('Location: index.php'); exit; }

// Handle form POST (save records)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $type = $_POST['mtype'];
    $period = $_POST['period'];
    $technician = trim($_POST['technician'] ?? '');
    $date = $_POST['completion_date'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $tasks = $db->prepare("SELECT * FROM maintenance_tasks WHERE maintenance_type=? ORDER BY sort_order");
    $tasks->execute([$type]);
    $all_tasks = $tasks->fetchAll();
    
    foreach ($all_tasks as $task) {
        $tk = $task['task_key'];
        $completed = isset($_POST['task'][$tk]) ? 1 : 0;
        
        $stmt = $db->prepare("
            INSERT INTO maintenance_records 
                (machine_id, maintenance_type, task_key, completed, completion_date, technician, notes, period_label, year)
            VALUES (?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE 
                completed=VALUES(completed),
                completion_date=VALUES(completion_date),
                technician=VALUES(technician),
                notes=VALUES(notes),
                updated_at=NOW()
        ");
        $stmt->execute([
            $machine_id, $type, $tk, $completed,
            $date ?: null, $technician, $notes, $period, $year
        ]);
    }
    
    $redirect = "machine.php?machine_id=$machine_id&year=$year&tab=$type&saved=1";
    if ($type !== 'monthly') $redirect .= "&period=" . urlencode($period);
    header("Location: $redirect");
    exit;
}

// Load tasks and records for each type
function getRecordsForPeriod($db, $machine_id, $type, $period, $year) {
    $stmt = $db->prepare("
        SELECT r.task_key, r.completed, r.completion_date, r.technician, r.notes
        FROM maintenance_records r
        WHERE r.machine_id=? AND r.maintenance_type=? AND r.period_label=? AND r.year=?
    ");
    $stmt->execute([$machine_id, $type, $period, $year]);
    $rows = $stmt->fetchAll();
    $map = [];
    foreach ($rows as $r) $map[$r['task_key']] = $r;
    return $map;
}

$types = ['monthly','quarterly','semi_annual','annual'];

// Summary per type
$summary = [];
foreach ($types as $type) {
    $periods = getPeriodsByType($type);
    $task_count = $db->prepare("SELECT COUNT(*) FROM maintenance_tasks WHERE maintenance_type=?");
    $task_count->execute([$type]);
    $tc = $task_count->fetchColumn();
    $done = $db->prepare("SELECT COUNT(*) FROM maintenance_records WHERE machine_id=? AND maintenance_type=? AND year=? AND completed=1");
    $done->execute([$machine_id, $type, $year]);
    $d = $done->fetchColumn();
    $total = $tc * count($periods);
    $summary[$type] = ['done'=>$d,'total'=>$total,'pct'=>$total>0?round($d/$total*100):0];
}

$years = range(date('Y') - 2, date('Y') + 1);
$saved = isset($_GET['saved']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=htmlspecialchars($machine['name'])?> – Maintenance</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="app-wrapper">
  <header class="app-header">
    <div class="header-inner">
      <div class="logo">
        <a href="index.php?year=<?=$year?>" class="back-btn">← Back</a>
        <div>
          <div class="logo-title"><?=htmlspecialchars($machine['name'])?></div>
          <div class="logo-sub"><?=htmlspecialchars($machine['serial_number'])?> · <?=htmlspecialchars($machine['location'])?></div>
        </div>
      </div>
      <div class="header-right">
        <form method="get" class="year-form">
          <input type="hidden" name="machine_id" value="<?=$machine_id?>">
          <input type="hidden" name="tab" value="<?=$active_tab?>">
          <label>Year:</label>
          <select name="year" onchange="this.form.submit()">
            <?php foreach($years as $y): ?>
            <option value="<?=$y?>" <?=$y==$year?'selected':''?>><?=$y?></option>
            <?php endforeach; ?>
          </select>
        </form>
        <a href="print_machine.php?machine_id=<?=$machine_id?>&year=<?=$year?>" class="btn btn-sm btn-outline" target="_blank">🖨 Print</a>
      </div>
    </div>
  </header>

  <main class="main-content">
    <?php if($saved): ?>
    <div class="alert alert-success">✅ Records saved successfully.</div>
    <?php endif; ?>

    <!-- Type Tabs -->
    <div class="type-tabs">
      <?php foreach($types as $t): 
        $s = $summary[$t];
        $color = $s['pct']>=80?'green':($s['pct']>=40?'orange':'red');
      ?>
      <a href="?machine_id=<?=$machine_id?>&year=<?=$year?>&tab=<?=$t?>" 
         class="type-tab <?=$active_tab===$t?'active':''?>">
        <span class="tab-label"><?=getTypeLabel($t)?></span>
        <span class="tab-badge <?=$color?>"><?=$s['done']?>/<?=$s['total']?></span>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Content for active tab -->
    <?php
    $type = $active_tab;
    $periods = getPeriodsByType($type);
    $tasks_stmt = $db->prepare("SELECT * FROM maintenance_tasks WHERE maintenance_type=? ORDER BY sort_order");
    $tasks_stmt->execute([$type]);
    $tasks = $tasks_stmt->fetchAll();
    
    $selected_period = isset($_GET['period']) ? $_GET['period'] : $periods[0];
    if (!in_array($selected_period, $periods)) $selected_period = $periods[0];
    $records = getRecordsForPeriod($db, $machine_id, $type, $selected_period, $year);
    
    // Get technician/date/notes from first record found
    $meta = ['technician'=>'','completion_date'=>'','notes'=>''];
    if (!empty($records)) {
        $first = reset($records);
        $meta['technician'] = $first['technician'] ?? '';
        $meta['completion_date'] = $first['completion_date'] ?? '';
        $meta['notes'] = $first['notes'] ?? '';
    }
    ?>

    <!-- Period selector -->
    <div class="period-selector">
      <?php foreach($periods as $p): 
        $prec = getRecordsForPeriod($db, $machine_id, $type, $p, $year);
        $done_count = array_sum(array_column($prec,'completed'));
        $total_tasks = count($tasks);
        $pp = $total_tasks>0?round($done_count/$total_tasks*100):0;
        $pc = $pp>=100?'done':($pp>0?'partial':'empty');
      ?>
      <a href="?machine_id=<?=$machine_id?>&year=<?=$year?>&tab=<?=$type?>&period=<?=urlencode($p)?>" 
         class="period-btn <?=$p===$selected_period?'active':''?> status-<?=$pc?>">
        <?=$p?>
        <?php if($type==='annual'): ?>
        <?php elseif($pp===100): ?><span class="period-check">✓</span>
        <?php elseif($pp>0): ?><span class="period-partial"><?=$pp?>%</span><?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Maintenance Form -->
    <form method="post" class="maintenance-form">
      <input type="hidden" name="mtype" value="<?=$type?>">
      <input type="hidden" name="period" value="<?=$selected_period?>">
      <input type="hidden" name="save" value="1">

      <div class="form-header">
        <h2><?=getTypeLabel($type)?> — <?=$selected_period?> <?=$type!=='annual'?$year:$year?></h2>
      </div>

      <div class="tasks-table-wrap">
        <table class="tasks-table">
          <thead>
            <tr>
              <th>Object</th>
              <th>Action</th>
              <th class="col-check">Done</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($tasks as $task): 
              $tk = $task['task_key'];
              $rec = $records[$tk] ?? null;
              $is_done = $rec && $rec['completed'];
            ?>
            <tr class="<?=$is_done?'row-done':''?>">
              <td><?=htmlspecialchars($task['object_name'])?></td>
              <td><?=htmlspecialchars($task['action_name'])?></td>
              <td class="col-check">
                <label class="checkbox-wrap">
                  <input type="checkbox" name="task[<?=htmlspecialchars($tk)?>]" value="1" <?=$is_done?'checked':''?>>
                  <span class="checkmark"></span>
                </label>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="form-meta">
        <div class="meta-field">
          <label>Completion Date</label>
          <input type="date" name="completion_date" value="<?=htmlspecialchars($meta['completion_date'])?>" class="input">
        </div>
        <div class="meta-field">
          <label>Technical Maintenance Person</label>
          <input type="text" name="technician" value="<?=htmlspecialchars($meta['technician'])?>" placeholder="Full name" class="input">
        </div>
        <div class="meta-field meta-full">
          <label>Notes (Optional)</label>
          <textarea name="notes" class="input" rows="2" placeholder="Any additional notes..."><?=htmlspecialchars($meta['notes'])?></textarea>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">💾 Save Records</button>
        <a href="print_machine.php?machine_id=<?=$machine_id?>&year=<?=$year?>&type=<?=$type?>&period=<?=urlencode($selected_period)?>" 
           class="btn btn-outline" target="_blank">🖨 Print This Form</a>
      </div>
    </form>
  </main>
</div>
</body>
</html>
