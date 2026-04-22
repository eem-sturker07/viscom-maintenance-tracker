<?php
require_once 'config.php';

$db = getDB();
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$machines = $db->query("SELECT * FROM machines ORDER BY name")->fetchAll();

function getAllRecords($db, $machine_id, $year) {
    $stmt = $db->prepare("SELECT * FROM maintenance_records WHERE machine_id=? AND year=?");
    $stmt->execute([$machine_id, $year]);
    $rows = $stmt->fetchAll();
    $map = [];
    foreach ($rows as $r) {
        $map[$r['maintenance_type']][$r['period_label']][$r['task_key']] = $r;
    }
    return $map;
}
$types = ['monthly','quarterly','semi_annual','annual'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>All Machines – Maintenance Report <?=$year?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size: 10px; color: #000; background:#fff; }
.machine-page { padding: 12mm; page-break-after: always; }
.machine-page:last-child { page-break-after: auto; }
.report-header { text-align:center; margin-bottom: 14px; border-bottom: 2px solid #000; padding-bottom: 8px; }
.report-header h1 { font-size: 15px; font-weight: bold; }
.report-header p { font-size: 11px; color: #444; margin-top:3px; }
.section { margin-bottom: 16px; page-break-inside: avoid; }
.section-title { background: #1a1a2e; color: #fff; padding: 5px 8px; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
table { width: 100%; border-collapse: collapse; margin-top: 0; }
th, td { border: 1px solid #aaa; padding: 4px 6px; text-align: left; }
th { background: #e8e8f0; font-weight: bold; font-size: 9px; text-transform: uppercase; }
.col-object { width: 22%; }
.col-action { width: 42%; }
.col-period { width: 5%; text-align: center; font-size: 9px; }
.done-cell { text-align: center; font-size: 12px; color: #1a7a1a; font-weight: bold; }
.empty-cell { text-align: center; color: #ddd; }
.no-print-btn { position:fixed; top:10px; right:10px; z-index:999; }
.no-print-btn a { background:#1a1a2e; color:#fff; padding:8px 16px; text-decoration:none; border-radius:4px; font-size:12px; display:inline-block; margin-left:8px; }
@media print {
    .no-print-btn { display:none; }
}
</style>
</head>
<body>
<div class="no-print-btn">
  <a href="javascript:window.print()">🖨 Print All</a>
  <a href="index.php?year=<?=$year?>">← Back</a>
</div>

<?php foreach ($machines as $machine): 
  $all_records = getAllRecords($db, $machine['id'], $year);
?>
<div class="machine-page">
  <div class="report-header">
    <h1>VISCOM MAINTENANCE TRACKING FORM</h1>
    <p><?=htmlspecialchars($machine['name'])?> &nbsp;|&nbsp; S/N: <?=htmlspecialchars($machine['serial_number'])?> &nbsp;|&nbsp; Location: <?=htmlspecialchars($machine['location'])?> &nbsp;|&nbsp; Year: <?=$year?></p>
  </div>

  <?php foreach($types as $type):
    $periods = getPeriodsByType($type);
    $tasks_stmt = $db->prepare("SELECT * FROM maintenance_tasks WHERE maintenance_type=? ORDER BY sort_order");
    $tasks_stmt->execute([$type]);
    $tasks = $tasks_stmt->fetchAll();
    if (empty($tasks)) continue;
  ?>
  <div class="section">
    <div class="section-title"><?=getTypeLabel($type)?></div>
    <table>
      <thead>
        <tr>
          <th class="col-object">Object</th>
          <th class="col-action">Action</th>
          <?php foreach($periods as $p): ?>
          <th class="col-period"><?=htmlspecialchars($p)?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach($tasks as $task): ?>
        <tr>
          <td class="col-object"><?=htmlspecialchars($task['object_name'])?></td>
          <td class="col-action"><?=htmlspecialchars($task['action_name'])?></td>
          <?php foreach($periods as $p): 
            $rec = $all_records[$type][$p][$task['task_key']] ?? null;
            $done = $rec && $rec['completed'];
          ?>
          <td class="<?=$done?'done-cell':'empty-cell'?>"><?=$done?'✓':'○'?></td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
        <tr>
          <td colspan="2" style="font-weight:bold;font-size:9px;">Date</td>
          <?php foreach($periods as $p):
            $date_val='';
            foreach($tasks as $task){$rec=$all_records[$type][$p][$task['task_key']]??null;if($rec&&$rec['completion_date']){$date_val=$rec['completion_date'];break;}}
          ?><td style="font-size:8px;text-align:center;"><?=$date_val?></td><?php endforeach; ?>
        </tr>
        <tr>
          <td colspan="2" style="font-weight:bold;font-size:9px;">Technical Maintenance Person</td>
          <?php foreach($periods as $p):
            $tech_val='';
            foreach($tasks as $task){$rec=$all_records[$type][$p][$task['task_key']]??null;if($rec&&$rec['technician']){$tech_val=$rec['technician'];break;}}
          ?><td style="font-size:8px;text-align:center;"><?=htmlspecialchars($tech_val)?></td><?php endforeach; ?>
        </tr>
      </tbody>
    </table>
  </div>
  <?php endforeach; ?>
</div>
<?php endforeach; ?>
</body>
</html>
