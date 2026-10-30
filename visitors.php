<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_login();
$db = db();
$u = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add']) && is_admin()) {
    verify_csrf('visitors.php');
    $name = trim($_POST['visitor_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $vehicle = strtoupper(trim($_POST['vehicle_no'] ?? ''));
    $flatId = (int)($_POST['flat_id'] ?? 0);
    $purpose = trim($_POST['purpose'] ?? '');
    if (strlen($name) >= 2 && $flatId > 0) {
        $db->prepare('INSERT INTO visitors (visitor_name, phone, vehicle_no, flat_id, purpose, added_by) VALUES (?, ?, ?, ?, ?, ?)')
           ->execute([$name, $phone ?: null, $vehicle ?: null, $flatId, $purpose ?: null, $u['id']]);
        set_flash('success', 'Visitor entry recorded at the gate.');
    } else set_flash('error', 'Visitor name and visiting flat are required.');
    redirect('visitors.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout']) && is_admin()) {
    verify_csrf('visitors.php');
    $db->prepare('UPDATE visitors SET out_time = NOW() WHERE id = ? AND out_time IS NULL')->execute([(int)$_POST['checkout']]);
    set_flash('success', 'Visitor checked out.');
    redirect('visitors.php');
}

$date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'] ?? '') ? $_GET['date'] : date('Y-m-d');
if (is_admin()) {
    $stmt = $db->prepare('SELECT v.*, f.block_name, f.flat_no FROM visitors v JOIN flats f ON f.id = v.flat_id WHERE DATE(v.in_time) = ? ORDER BY v.in_time DESC');
    $stmt->execute([$date]);
} else {
    $stmt = $db->prepare('SELECT v.*, f.block_name, f.flat_no FROM visitors v JOIN flats f ON f.id = v.flat_id WHERE v.flat_id = ? ORDER BY v.in_time DESC LIMIT 50');
    $stmt->execute([$u['flat_id'] ?? 0]);
}
$visitors = $stmt->fetchAll();
$flats = $db->query('SELECT * FROM flats ORDER BY block_name, CAST(flat_no AS UNSIGNED)')->fetchAll();

$pageTitle = 'Visitors';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Visitors — Gate Register</h1><p><?= is_admin() ? 'Log every visitor entering the society and check them out when they leave' : 'Visitors who came to your flat' ?></p></div>
<?php if ($s = flash('success')): ?><div class="alert alert-success"><?= e($s) ?></div><?php endif; ?>
<?php if ($f = flash('error')): ?><div class="alert alert-error"><?= e($f) ?></div><?php endif; ?>

<?php if (is_admin()): ?>
<div class="card" style="margin-bottom:22px">
  <h2 style="font-size:1.08rem;margin-bottom:14px">New Visitor Entry</h2>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="add" value="1">
    <div class="form-grid">
      <div class="field"><label>Visitor Name *</label><input type="text" name="visitor_name" required placeholder="Visitor name"></div>
      <div class="field"><label>Mobile</label><input type="text" name="phone" placeholder="Mobile number"></div>
      <div class="field"><label>Visiting Flat *</label>
        <select name="flat_id" required>
          <option value="">— Select flat —</option>
          <?php foreach ($flats as $f): ?><option value="<?= (int)$f['id'] ?>"><?= e(flat_label($f)) ?></option><?php endforeach; ?>
        </select></div>
      <div class="field"><label>Vehicle No. (if any)</label><input type="text" name="vehicle_no" placeholder="e.g. BR01AB1234" style="text-transform:uppercase"></div>
      <div class="field" style="grid-column:1/-1"><label>Purpose</label><input type="text" name="purpose" placeholder="e.g. Guest, Delivery, Maid interview..."></div>
    </div>
    <button class="btn" type="submit">Record Entry (In)</button>
  </form>
</div>
<div class="filter-card">
  <form method="get" style="display:flex;gap:10px">
    <input type="date" name="date" value="<?= e($date) ?>" style="padding:11px 13px;border:1.5px solid #D8D2C4;border-radius:11px;font:inherit">
    <button class="btn" type="submit">View Date</button>
    <a class="btn btn-light" href="visitors.php">Today</a>
  </form>
</div>
<?php endif; ?>

<div class="card">
  <div class="table-wrap"><table>
    <tr><th>Visitor</th><th>Visiting Flat</th><th>Purpose</th><th>In Time</th><th>Out Time</th><th>Status</th><?php if (is_admin()): ?><th>Action</th><?php endif; ?></tr>
    <?php if (!$visitors): ?><tr><td colspan="7" style="text-align:center;color:var(--muted);padding:24px">No visitors recorded<?= is_admin() ? ' for this date' : '' ?>.</td></tr><?php endif; ?>
    <?php foreach ($visitors as $v): ?>
    <tr>
      <td><strong><?= e($v['visitor_name']) ?></strong><br><small style="color:var(--muted)"><?= e($v['phone'] ?: '') ?><?= $v['vehicle_no'] ? ' &bull; ' . e($v['vehicle_no']) : '' ?></small></td>
      <td><strong><?= e($v['block_name'] . '-' . $v['flat_no']) ?></strong></td>
      <td><?= e($v['purpose'] ?: '-') ?></td>
      <td><?= e(date('d M, h:i A', strtotime($v['in_time']))) ?></td>
      <td><?= $v['out_time'] ? e(date('d M, h:i A', strtotime($v['out_time']))) : '-' ?></td>
      <td><?= $v['out_time'] ? '<span class="pill pill-closed">Checked out</span>' : '<span class="pill pill-progress">Inside</span>' ?></td>
      <?php if (is_admin()): ?><td><?php if (!$v['out_time']): ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="checkout" value="<?= (int)$v['id'] ?>"><button class="btn btn-sm" type="submit">Check Out</button></form>
      <?php endif; ?></td><?php endif; ?>
    </tr>
    <?php endforeach; ?>
  </table></div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
