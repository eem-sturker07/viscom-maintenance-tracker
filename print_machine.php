<?php
require_once 'config.php';

$db = getDB();
$machine_id = isset($_GET['machine_id']) ? (int)$_GET['machine_id'] : 0;
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$filter_type = isset($_GET['type']) ? $_GET['type'] : null;
$filter_period = isset($_GET['period']) ? $_GET['period'] : null;

if (!$machine_id) { header('Location: index.php'); exit; }

$machine = $db->prepare("SELECT * FROM machines WHERE id=?");
$machine->execute([$machine_id]);
$machine = $machine->fetch();
if (!$machine) { header('Location: index.php'); exit; }

function getRecords($db, $machine_id, $year) {
    $stmt = $db->prepare("SELECT * FROM maintenance_records WHERE machine_id=? AND year=?");
    $stmt->execute([$machine_id, $year]);
    $rows = $stmt->fetchAll();
    $map = [];
    foreach ($rows as $r) {
        $map[$r['maintenance_type']][$r['period_label']][$r['task_key']] = $r;
    }
    return $map;
}

$all_records = getRecords($db, $machine_id, $year);
$types = ['monthly','quarterly','semi_annual','annual'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?=htmlspecialchars($machine['name'])?> – Maintenance Report <?=$year?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size: 11px; color: #000; background:#fff; }
.page { padding: 15mm 15mm 10mm 15mm; }
.report-header { text-align:center; margin-bottom: 16px; border-bottom: 2px solid #000; padding-bottom: 10px; }
.report-header h1 { font-size: 18px; font-weight: bold; }
.report-header p { font-size: 12px; color: #555; margin-top:4px; }
.section { margin-bottom: 22px; page-break-inside: avoid; }
.section-title { background: #1a1a2e; color: #fff; padding: 6px 10px; font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #999; padding: 5px 8px; text-align: left; }
th { background: #e8e8f0; font-weight: bold; font-size: 10px; text-transform: uppercase; }
.col-object { width: 25%; }
.col-action { width: 45%; }
.col-period { width: 6%; text-align: center; font-size: 10px; }
.done-cell { text-align: center; font-size: 14px; color: #1a7a1a; font-weight: bold; }
.empty-cell { text-align: center; color: #ccc; }
.meta-row { margin-top: 6px; display: flex; gap: 20px; }
.meta-item { flex: 1; border: 1px solid #aaa; padding: 5px 8px; }
.meta-label { font-size: 9px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }
.meta-value { font-size: 11px; font-weight: bold; min-height: 16px; }
.no-print-btn { position:fixed; top:10px; right:10px; }
.no-print-btn a { background:#1a1a2e; color:#fff; padding:8px 16px; text-decoration:none; border-radius:4px; font-size:12px; display:inline-block; margin-left:8px; }
@media print {
    .no-print-btn { display:none; }
    body { font-size: 10px; }
    .page { padding: 10mm; }
    .section { page-break-inside: avoid; }
}
</style>
</head>
<body>
<div class="no-print-btn">
  <a href="javascript:window.print()">🖨 Print</a>
  <a href="machine.php?machine_id=<?=$machine_id?>&year=<?=$year?>">← Back</a>
</div>

<div class="page">
  <div class="report-header">
    <h1>VISCOM MAINTENANCE TRACKING FORM</h1>
    <p><?=htmlspecialchars($machine['name'])?> &nbsp;|&nbsp; S/N: <?=htmlspecialchars($machine['serial_number'])?> &nbsp;|&nbsp; Location: <?=htmlspecialchars($machine['location'])?> &nbsp;|&nbsp; Year: <?=$year?></p>
  </div>

  <?php foreach($types as $type):
    if ($filter_type && $type !== $filter_type) continue;
    $periods = getPeriodsByType($type);
    $tasks_stmt = $db->prepare("SELECT * FROM maintenance_tasks WHERE maintenance_type=? ORDER BY sort_order");
    $tasks_stmt->execute([$type]);
    $tasks = $tasks_stmt->fetchAll();
    if (empty($tasks)) continue;
    
    if ($filter_period) {
        $show_periods = [$filter_period];
    } else {
        $show_periods = $periods;
    }
  ?>
  <div class="section">
    <div class="section-title"><?=getTypeLabel($type)?></div>
    <table>
      <thead>
        <tr>
          <th class="col-object">Object</th>
          <th class="col-action">Action</th>
          <?php foreach($show_periods as $p): ?>
          <th class="col-period"><?=htmlspecialchars($p)?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach($tasks as $task): ?>
        <tr>
          <td class="col-object"><?=htmlspecialchars($task['object_name'])?></td>
          <td class="col-action"><?=htmlspecialchars($task['action_name'])?></td>
          <?php foreach($show_periods as $p): 
            $rec = $all_records[$type][$p][$task['task_key']] ?? null;
            $done = $rec && $rec['completed'];
          ?>
          <td class="<?=$done?'done-cell':'empty-cell'?>">
            <?=$done ? '✓' : '○'?>
          </td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
        <!-- Date and technician rows -->
        <tr>
          <td colspan="2" style="font-weight:bold;">Date</td>
          <?php foreach($show_periods as $p):
            // Get date from first completed record in this period
            $date_val = '';
            foreach($tasks as $task) {
              $rec = $all_records[$type][$p][$task['task_key']] ?? null;
              if ($rec && $rec['completion_date']) { $date_val = $rec['completion_date']; break; }
            }
          ?>
          <td style="font-size:9px; text-align:center;"><?=$date_val?></td>
          <?php endforeach; ?>
        </tr>
        <tr>
          <td colspan="2" style="font-weight:bold;">Technical Maintenance Person</td>
          <?php foreach($show_periods as $p):
            $tech_val = '';
            foreach($tasks as $task) {
              $rec = $all_records[$type][$p][$task['task_key']] ?? null;
              if ($rec && $rec['technician']) { $tech_val = $rec['technician']; break; }
            }
          ?>
          <td style="font-size:9px; text-align:center;"><?=htmlspecialchars($tech_val)?></td>
          <?php endforeach; ?>
        </tr>
      </tbody>
    </table>
  </div>
  <?php endforeach; ?>

  <div style="margin-top:20px; font-size:9px; color:#888; text-align:right;">
    Generated: <?=date('Y-m-d H:i')?> · Viscom Maintenance Tracking System
  </div>
</div>
</body>
</html>
