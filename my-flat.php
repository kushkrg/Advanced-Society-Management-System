<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_login();
if (is_admin()) redirect('flats.php');
$db = db();
$u = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member'])) {
    verify_csrf('my-flat.php');
    $name = trim($_POST['name'] ?? '');
    $relation = trim($_POST['relation'] ?? '');
    $age = ($_POST['age'] ?? '') !== '' ? (int)$_POST['age'] : null;
    $phone = trim($_POST['phone'] ?? '');
    if (strlen($name) >= 2) {
        $db->prepare('INSERT INTO family_members (user_id, name, relation, age, phone) VALUES (?, ?, ?, ?, ?)')
           ->execute([$u['id'], $name, $relation ?: 'Family', $age, $phone ?: null]);
        set_flash('success', 'Family member added.');
    } else set_flash('error', 'Please enter the member name.');
    redirect('my-flat.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_member'])) {
    verify_csrf('my-flat.php');
    $db->prepare('DELETE FROM family_members WHERE id = ? AND user_id = ?')->execute([(int)$_POST['delete_member'], $u['id']]);
    set_flash('success', 'Family member removed.');
    redirect('my-flat.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_vehicle'])) {
    verify_csrf('my-flat.php');
    $no = strtoupper(trim($_POST['vehicle_no'] ?? ''));
    $type = ($_POST['vehicle_type'] ?? 'Car') === 'Bike' ? 'Bike' : 'Car';
    $model = trim($_POST['model'] ?? '');
    if (strlen($no) >= 4) {
        try {
            $db->prepare('INSERT INTO vehicles (user_id, vehicle_no, vehicle_type, model) VALUES (?, ?, ?, ?)')
               ->execute([$u['id'], $no, $type, $model ?: null]);
            set_flash('success', 'Vehicle added. Parking charges apply as per society rates.');
        } catch (PDOException $ex) { set_flash('error', 'This vehicle number is already registered.'); }
    } else set_flash('error', 'Enter a valid vehicle number.');
    redirect('my-flat.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_vehicle'])) {
    verify_csrf('my-flat.php');
    $db->prepare('DELETE FROM vehicles WHERE id = ? AND user_id = ?')->execute([(int)$_POST['delete_vehicle'], $u['id']]);
    set_flash('success', 'Vehicle removed.');
    redirect('my-flat.php');
}

$flat = null;
if (!empty($u['flat_id'])) {
    $stmt = $db->prepare('SELECT * FROM flats WHERE id = ?');
    $stmt->execute([$u['flat_id']]);
    $flat = $stmt->fetch();
}
$family = $db->prepare('SELECT * FROM family_members WHERE user_id = ? ORDER BY name');
$family->execute([$u['id']]);
$family = $family->fetchAll();
$vehicles = $db->prepare('SELECT * FROM vehicles WHERE user_id = ? ORDER BY vehicle_no');
$vehicles->execute([$u['id']]);
$vehicles = $vehicles->fetchAll();

$pageTitle = 'My Flat & Family';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>My Flat &amp; Family</h1><p>Your flat details, family members and vehicles</p></div>
<?php if ($s = flash('success')): ?><div class="alert alert-success"><?= e($s) ?></div><?php endif; ?>
<?php if ($f = flash('error')): ?><div class="alert alert-error"><?= e($f) ?></div><?php endif; ?>
<?php if (!$flat): ?>
<div class="alert alert-error" style="background:#FFFBEB;border-color:#FDE68A;color:#92400E">No flat is linked to your account yet. Please contact the society office.</div>
<?php else: ?>
<div class="card" style="margin-bottom:22px">
  <h2 style="font-size:1.2rem">Flat <?= e(flat_label($flat)) ?></h2>
  <dl class="kv" style="margin-top:10px">
    <dt>Type</dt><dd><?= e($flat['flat_type']) ?></dd>
    <dt>Floor</dt><dd><?= (int)$flat['floor_no'] ?></dd>
    <dt>Area</dt><dd><?= $flat['area_sqft'] ? (int)$flat['area_sqft'] . ' sq.ft.' : '-' ?></dd>
    <dt>Vehicles</dt><dd><?= count($vehicles) ?> (parking is billed per vehicle)</dd>
  </dl>
</div>
<?php endif; ?>
<div class="detail-grid">
  <div class="card">
    <h2>Family Members</h2>
    <?php foreach ($family as $fm): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--line)">
      <span><strong><?= e($fm['name']) ?></strong> <small style="color:var(--muted)"><?= e($fm['relation']) ?><?= $fm['age'] ? ' &bull; Age ' . (int)$fm['age'] : '' ?><?= $fm['phone'] ? ' &bull; ' . e($fm['phone']) : '' ?></small></span>
      <form method="post" data-confirm="Remove this family member?"><?= csrf_field() ?><input type="hidden" name="delete_member" value="<?= (int)$fm['id'] ?>"><button class="btn btn-sm btn-danger" type="submit">Remove</button></form>
    </div>
    <?php endforeach; ?>
    <?php if (!$family): ?><p style="color:var(--muted)">No family members added.</p><?php endif; ?>
    <form method="post" style="margin-top:16px">
      <?= csrf_field() ?><input type="hidden" name="add_member" value="1">
      <div class="form-grid">
        <div class="field"><label>Name</label><input type="text" name="name" required placeholder="Member name"></div>
        <div class="field"><label>Relation</label><input type="text" name="relation" placeholder="Wife / Son / Mother..."></div>
        <div class="field"><label>Age</label><input type="number" name="age" min="0" max="120"></div>
        <div class="field"><label>Mobile</label><input type="text" name="phone" placeholder="Optional"></div>
      </div>
      <button class="btn" type="submit">+ Add Member</button>
    </form>
  </div>
  <div class="card">
    <h2>My Vehicles</h2>
    <?php foreach ($vehicles as $v): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--line)">
      <span><strong><?= e($v['vehicle_no']) ?></strong> <small style="color:var(--muted)"><?= e($v['vehicle_type']) ?><?= $v['model'] ? ' &bull; ' . e($v['model']) : '' ?></small></span>
      <form method="post" data-confirm="Remove this vehicle?"><?= csrf_field() ?><input type="hidden" name="delete_vehicle" value="<?= (int)$v['id'] ?>"><button class="btn btn-sm btn-danger" type="submit">Remove</button></form>
    </div>
    <?php endforeach; ?>
    <?php if (!$vehicles): ?><p style="color:var(--muted)">No vehicles registered.</p><?php endif; ?>
    <form method="post" style="margin-top:16px">
      <?= csrf_field() ?><input type="hidden" name="add_vehicle" value="1">
      <div class="form-grid">
        <div class="field"><label>Vehicle Number</label><input type="text" name="vehicle_no" required placeholder="e.g. BR01AB1234" style="text-transform:uppercase"></div>
        <div class="field"><label>Type</label><select name="vehicle_type"><option>Car</option><option>Bike</option></select></div>
        <div class="field" style="grid-column:1/-1"><label>Model (optional)</label><input type="text" name="model" placeholder="e.g. Maruti Swift"></div>
      </div>
      <button class="btn" type="submit">+ Add Vehicle</button>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
