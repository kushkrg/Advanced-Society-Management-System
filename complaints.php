<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_login();
$db = db();
$u = current_user();
$CATEGORIES = ['Plumbing', 'Electrical', 'Lift', 'Cleaning', 'Security', 'Parking', 'Water', 'Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['raise'])) {
    verify_csrf('complaints.php');
    $title = trim($_POST['title'] ?? '');
    $category = in_array($_POST['category'] ?? '', $CATEGORIES, true) ? $_POST['category'] : 'Other';
    $priority = in_array($_POST['priority'] ?? '', ['Low', 'Medium', 'High'], true) ? $_POST['priority'] : 'Medium';
    $desc = trim($_POST['description'] ?? '');
    if (strlen($title) < 4) {
        set_flash('error', 'Please give your complaint a clear title.');
    } else {
        $db->prepare('INSERT INTO complaints (user_id, flat_id, title, category, priority, description) VALUES (?, ?, ?, ?, ?, ?)')
           ->execute([$u['id'], $u['flat_id'] ?: null, $title, $category, $priority, $desc ?: null]);
        set_flash('success', 'Complaint raised. The society office will update its status here.');
    }
    redirect('complaints.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status']) && is_admin()) {
    verify_csrf('complaints.php');
    $cid = (int)$_POST['complaint_id'];
    $status = in_array($_POST['status'] ?? '', ['open', 'in_progress', 'resolved', 'closed'], true) ? $_POST['status'] : 'open';
    $remark = trim($_POST['admin_remark'] ?? '');
    $db->prepare('UPDATE complaints SET status = ?, admin_remark = ? WHERE id = ?')->execute([$status, $remark ?: null, $cid]);
    set_flash('success', 'Complaint updated.');
    redirect('complaints.php');
}

$statusFilter = in_array($_GET['status'] ?? '', ['open', 'in_progress', 'resolved', 'closed'], true) ? $_GET['status'] : '';
if (is_admin()) {
    $sql = "SELECT c.*, usr.name AS res_name, f.block_name, f.flat_no FROM complaints c JOIN users usr ON usr.id = c.user_id LEFT JOIN flats f ON f.id = c.flat_id" . ($statusFilter ? ' WHERE c.status = ?' : '') . ' ORDER BY c.id DESC LIMIT 100';
    $stmt = $db->prepare($sql);
    $stmt->execute($statusFilter ? [$statusFilter] : []);
} else {
    $stmt = $db->prepare('SELECT c.* FROM complaints c WHERE c.user_id = ? ORDER BY c.id DESC LIMIT 50');
    $stmt->execute([$u['id']]);
}
$complaints = $stmt->fetchAll();
$statusPill = ['open' => 'pill-open', 'in_progress' => 'pill-progress', 'resolved' => 'pill-resolved', 'closed' => 'pill-closed'];
$pageTitle = 'Complaints';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Complaints &amp; Service Requests</h1><p><?= is_admin() ? 'All resident complaints — update status as work progresses' : 'Raise a complaint and track it here' ?></p></div>
<?php if ($s = flash('success')): ?><div class="alert alert-success"><?= e($s) ?></div><?php endif; ?>
<?php if ($f = flash('error')): ?><div class="alert alert-error"><?= e($f) ?></div><?php endif; ?>

<?php if (!is_admin()): ?>
<div class="card" style="margin-bottom:22px">
  <h2 style="font-size:1.08rem;margin-bottom:14px">Raise a Complaint</h2>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="raise" value="1">
    <div class="form-grid">
      <div class="field" style="grid-column:1/-1"><label>Title *</label><input type="text" name="title" required placeholder="e.g. Water leakage in bathroom"></div>
      <div class="field"><label>Category</label>
        <select name="category"><?php foreach ($CATEGORIES as $c): ?><option><?= e($c) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Priority</label>
        <select name="priority"><option>Low</option><option selected>Medium</option><option>High</option></select></div>
      <div class="field" style="grid-column:1/-1"><label>Details</label><textarea name="description" rows="3" placeholder="Describe the problem..."></textarea></div>
    </div>
    <button class="btn" type="submit">Submit Complaint</button>
  </form>
</div>
<?php endif; ?>

<?php if (is_admin()): ?>
<div class="filter-card">
  <a class="btn btn-sm <?= $statusFilter === '' ? '' : 'btn-light' ?>" href="complaints.php">All</a>
  <a class="btn btn-sm <?= $statusFilter === 'open' ? '' : 'btn-light' ?>" href="complaints.php?status=open">Open</a>
  <a class="btn btn-sm <?= $statusFilter === 'in_progress' ? '' : 'btn-light' ?>" href="complaints.php?status=in_progress">In Progress</a>
  <a class="btn btn-sm <?= $statusFilter === 'resolved' ? '' : 'btn-light' ?>" href="complaints.php?status=resolved">Resolved</a>
  <a class="btn btn-sm <?= $statusFilter === 'closed' ? '' : 'btn-light' ?>" href="complaints.php?status=closed">Closed</a>
</div>
<?php endif; ?>

<?php if (!$complaints): ?><div class="card" style="text-align:center;color:var(--muted)">No complaints found.</div><?php endif; ?>
<?php foreach ($complaints as $c): ?>
<div class="notice-card">
  <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <strong style="font-size:1.05rem"><?= e($c['title']) ?></strong>
    <span class="pill <?= $statusPill[$c['status']] ?>"><?= e(str_replace('_', ' ', $c['status'])) ?></span>
  </div>
  <small style="color:var(--muted)"><?= e($c['category']) ?> &bull; Priority: <?= e($c['priority']) ?> &bull; <?= e(date('d M Y, h:i A', strtotime($c['created_at']))) ?><?php if (is_admin()): ?> &bull; by <?= e($c['res_name']) ?><?= $c['flat_no'] ? ' (' . e($c['block_name'] . '-' . $c['flat_no']) . ')' : '' ?><?php endif; ?></small>
  <?php if ($c['description']): ?><p style="margin-top:8px;color:var(--muted)"><?= e($c['description']) ?></p><?php endif; ?>
  <?php if ($c['admin_remark']): ?><p style="margin-top:8px;background:var(--teal-soft);border-radius:10px;padding:10px 12px"><strong>Office:</strong> <?= e($c['admin_remark']) ?></p><?php endif; ?>
  <?php if (is_admin()): ?>
  <form method="post" style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap">
    <?= csrf_field() ?><input type="hidden" name="complaint_id" value="<?= (int)$c['id'] ?>">
    <select name="status" style="width:auto;padding:8px 10px">
      <?php foreach (['open', 'in_progress', 'resolved', 'closed'] as $st): ?><option value="<?= $st ?>" <?= $c['status'] === $st ? 'selected' : '' ?>><?= e(str_replace('_', ' ', $st)) ?></option><?php endforeach; ?>
    </select>
    <input type="text" name="admin_remark" placeholder="Remark for the resident..." style="flex:1;min-width:180px;padding:9px 12px;border:1.5px solid #D8D2C4;border-radius:10px;font:inherit" value="<?= e($c['admin_remark'] ?? '') ?>">
    <button class="btn btn-sm" type="submit" name="update_status" value="1">Update</button>
  </form>
  <?php endif; ?>
</div>
<?php endforeach; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
