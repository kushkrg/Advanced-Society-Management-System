<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_login();
$db = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add']) && is_admin()) {
    verify_csrf('facilities.php');
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $capacity = ($_POST['capacity'] ?? '') !== '' ? (int)$_POST['capacity'] : null;
    $charges = max(0, (float)($_POST['charges'] ?? 0));
    if (strlen($name) >= 2) {
        try {
            $db->prepare('INSERT INTO facilities (name, description, capacity, charges) VALUES (?, ?, ?, ?)')
               ->execute([$name, $desc ?: null, $capacity, $charges]);
            set_flash('success', 'Facility added.');
        } catch (PDOException $ex) { set_flash('error', 'A facility with this name already exists.'); }
    } else set_flash('error', 'Facility name is required.');
    redirect('facilities.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id']) && is_admin()) {
    verify_csrf('facilities.php');
    $db->prepare('UPDATE facilities SET is_active = 1 - is_active WHERE id = ?')->execute([(int)$_POST['toggle_id']]);
    redirect('facilities.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id']) && is_admin()) {
    verify_csrf('facilities.php');
    $db->prepare('DELETE FROM facilities WHERE id = ?')->execute([(int)$_POST['delete_id']]);
    set_flash('success', 'Facility deleted.');
    redirect('facilities.php');
}
$facilities = $db->query('SELECT * FROM facilities ORDER BY name')->fetchAll();
$pageTitle = 'Facilities';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Society Facilities</h1><p>Common amenities residents can book</p></div>
<?php if ($s = flash('success')): ?><div class="alert alert-success"><?= e($s) ?></div><?php endif; ?>
<?php if ($f = flash('error')): ?><div class="alert alert-error"><?= e($f) ?></div><?php endif; ?>
<?php if (is_admin()): ?>
<div class="card" style="margin-bottom:22px">
  <h2 style="font-size:1.08rem;margin-bottom:14px">Add Facility</h2>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="add" value="1">
    <div class="form-grid">
      <div class="field"><label>Name *</label><input type="text" name="name" required placeholder="e.g. Community Hall"></div>
      <div class="field"><label>Capacity (people)</label><input type="number" name="capacity" min="1" placeholder="e.g. 100"></div>
      <div class="field"><label>Booking Charges (₹ / day-slot)</label><input type="number" name="charges" min="0" step="0.01" value="0"></div>
      <div class="field"><label>Short Description</label><input type="text" name="description" placeholder="e.g. For functions and parties"></div>
    </div>
    <button class="btn" type="submit">Add Facility</button>
  </form>
</div>
<?php endif; ?>
<div class="flat-grid" style="grid-template-columns:repeat(auto-fill,minmax(240px,1fr))">
<?php if (!$facilities): ?><div class="card" style="text-align:center;color:var(--muted)">No facilities added yet.</div><?php endif; ?>
<?php foreach ($facilities as $fac): ?>
  <div class="flat-card" style="text-align:left;<?= !(int)$fac['is_active'] ? 'opacity:.55' : '' ?>">
    <strong style="font-size:1.08rem"><?= e($fac['name']) ?></strong>
    <p style="color:var(--muted);margin-top:5px"><?= e($fac['description'] ?: '') ?></p>
    <p style="margin-top:8px"><strong><?= (float)$fac['charges'] > 0 ? e(inr((float)$fac['charges'])) : 'Free' ?></strong><?= $fac['capacity'] ? ' &bull; up to ' . (int)$fac['capacity'] . ' people' : '' ?></p>
    <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap">
      <?php if (!is_admin() && (int)$fac['is_active']): ?><a class="btn btn-sm" href="book-facility.php?facility_id=<?= (int)$fac['id'] ?>">Book Now</a><?php endif; ?>
      <?php if (is_admin()): ?>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="toggle_id" value="<?= (int)$fac['id'] ?>"><button class="btn btn-sm btn-light" type="submit"><?= (int)$fac['is_active'] ? 'Deactivate' : 'Activate' ?></button></form>
      <form method="post" data-confirm="Delete this facility and its bookings?"><?= csrf_field() ?><input type="hidden" name="delete_id" value="<?= (int)$fac['id'] ?>"><button class="btn btn-sm btn-danger" type="submit">Delete</button></form>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
