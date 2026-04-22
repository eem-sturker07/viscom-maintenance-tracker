<?php
require_once 'config.php';
$db = getDB();
$msg = '';

// Add machine
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_machine'])) {
    $name = trim($_POST['name']);
    $serial = trim($_POST['serial_number']);
    $location = trim($_POST['location']);
    if ($name) {
        $db->prepare("INSERT INTO machines (name, serial_number, location) VALUES (?,?,?)")
           ->execute([$name, $serial, $location]);
        $msg = "✅ Machine '$name' added.";
    }
}
// Edit machine
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['edit_machine'])) {
    $id = (int)$_POST['edit_id'];
    $name = trim($_POST['name']);
    $serial = trim($_POST['serial_number']);
    $location = trim($_POST['location']);
    if ($name && $id) {
        $db->prepare("UPDATE machines SET name=?, serial_number=?, location=? WHERE id=?")
           ->execute([$name, $serial, $location, $id]);
        $msg = "✅ Machine updated.";
    }
}
// Delete machine
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_machine'])) {
    $id = (int)$_POST['delete_id'];
    $db->prepare("DELETE FROM machines WHERE id=?")->execute([$id]);
    $msg = "🗑 Machine deleted.";
}

$machines = $db->query("SELECT * FROM machines ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Settings – Viscom Maintenance</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="app-wrapper">
  <header class="app-header">
    <div class="header-inner">
      <div class="logo">
        <a href="index.php" class="back-btn">← Back</a>
        <div>
          <div class="logo-title">Settings</div>
          <div class="logo-sub">Manage Machines</div>
        </div>
      </div>
    </div>
  </header>
  <main class="main-content">
    <?php if($msg): ?>
    <div class="alert alert-success"><?=$msg?></div>
    <?php endif; ?>

    <div class="settings-section">
      <h2>Machines</h2>
      <table class="data-table">
        <thead><tr><th>Name</th><th>Serial No.</th><th>Location</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach($machines as $m): ?>
          <tr>
            <td><?=htmlspecialchars($m['name'])?></td>
            <td><?=htmlspecialchars($m['serial_number'])?></td>
            <td><?=htmlspecialchars($m['location'])?></td>
            <td>
              <button class="btn btn-sm btn-outline" onclick="editMachine(<?=$m['id']?>, '<?=addslashes($m['name'])?>', '<?=addslashes($m['serial_number'])?>', '<?=addslashes($m['location'])?>')">Edit</button>
              <form method="post" style="display:inline" onsubmit="return confirm('Delete this machine and all its records?')">
                <input type="hidden" name="delete_id" value="<?=$m['id']?>">
                <button type="submit" name="delete_machine" class="btn btn-sm btn-danger">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="add-form" id="machineForm">
        <h3 id="formTitle">Add New Machine</h3>
        <form method="post">
          <input type="hidden" name="edit_id" id="edit_id">
          <div class="form-row">
            <div class="meta-field">
              <label>Machine Name *</label>
              <input type="text" name="name" id="f_name" class="input" required placeholder="e.g. Viscom 1">
            </div>
            <div class="meta-field">
              <label>Serial Number</label>
              <input type="text" name="serial_number" id="f_serial" class="input" placeholder="e.g. VS-001">
            </div>
            <div class="meta-field">
              <label>Location</label>
              <input type="text" name="location" id="f_location" class="input" placeholder="e.g. Hat 1">
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" name="add_machine" id="submitBtn" class="btn btn-primary">Add Machine</button>
            <button type="button" onclick="resetForm()" class="btn btn-outline">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </main>
</div>
<script>
function editMachine(id, name, serial, location) {
  document.getElementById('formTitle').textContent = 'Edit Machine';
  document.getElementById('edit_id').value = id;
  document.getElementById('f_name').value = name;
  document.getElementById('f_serial').value = serial;
  document.getElementById('f_location').value = location;
  document.getElementById('submitBtn').name = 'edit_machine';
  document.getElementById('submitBtn').textContent = 'Save Changes';
  document.getElementById('machineForm').scrollIntoView({behavior:'smooth'});
}
function resetForm() {
  document.getElementById('formTitle').textContent = 'Add New Machine';
  document.getElementById('edit_id').value = '';
  document.getElementById('f_name').value = '';
  document.getElementById('f_serial').value = '';
  document.getElementById('f_location').value = '';
  document.getElementById('submitBtn').name = 'add_machine';
  document.getElementById('submitBtn').textContent = 'Add Machine';
}
</script>
</body>
</html>
