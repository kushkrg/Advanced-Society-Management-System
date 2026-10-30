<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_admin();
$db = db();
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT u.*, f.block_name, f.flat_no, f.flat_type FROM users u LEFT JOIN flats f ON f.id = u.flat_id WHERE u.id = ? AND u.role = 'resident'");
$stmt->execute([$id]);
$res = $stmt->fetch();
if (!$res) { set_flash('error', 'Resident not found.'); redirect('residents.php'); }
$family = $db->prepare('SELECT * FROM family_members WHERE user_id = ? ORDER BY name');
$family->execute([$id]);
$family = $family->fetchAll();
$vehicles = $db->prepare('SELECT * FROM vehicles WHERE user_id = ? ORDER BY vehicle_no');
$vehicles->execute([$id]);
$vehicles = $vehicles->fetchAll();
$dues = 0.0;
if ($res['flat_id']) {
    $stmt = $db->prepare("SELECT COALESCE(SUM(total_amount - paid_amount),0) FROM bills WHERE flat_id = ? AND status IN ('unpaid','partial')");
    $stmt->execute([$res['flat_id']]);
    $dues = (float)$stmt->fetchColumn();
}
$pageTitle = 'Resident Profile';
require_once __DIR__ . '/includes/header.php';
?>
<?php if ($s = flash('success')): ?><div class="alert alert-success"><?= e($s) ?></div><?php endif; ?>
<div class="detail-hero">
  <div class="detail-avatar"><?= e(initials($res['name'])) ?></div>
  <div>
    <h1><?= e($res['name']) ?></h1>
    <div class="meta"><?= $res['flat_no'] ? 'Flat ' . e($res['block_name'] . '-' . $res['flat_no']) . ' (' . e($res['flat_type']) . ')' : 'No flat assigned' ?> &bull; <span style="text-transform:capitalize"><?= e($res['resident_type']) ?></span></div>
    <div class="meta"><?= e($res['email']) ?><?= $res['phone'] ? ' &bull; ' . e($res['phone']) : '' ?></div>
    <div class="badges">
      <span class="badge <?= (int)$res['is_active'] === 1 ? 'badge-active' : 'badge-inactive' ?>"><?= (int)$res['is_active'] === 1 ? 'Active' : 'Inactive' ?></span>
      <?php if ($dues > 0): ?><span class="badge badge-inactive">Dues: <?= e(inr($dues)) ?></span><?php endif; ?>
    </div>
  </div>
</div>
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:22px">
  <a class="btn" href="resident-edit.php?id=<?= $id ?>">Edit Resident</a>
  <?php if ($res['flat_id']): ?><a class="btn btn-light" href="flat-view.php?id=<?= (int)$res['flat_id'] ?>">View Flat</a><a class="btn btn-light" href="bills.php?flat_id=<?= (int)$res['flat_id'] ?>">Flat Bills</a><?php endif; ?>
  <a class="btn btn-light" href="residents.php">All Residents</a>
</div>
<div class="detail-grid">
  <div class="card"><h2>Family Members</h2>
    <?php if (!$family): ?><p style="color:var(--muted)">No family members added yet (the resident can add them from My Flat).</p><?php endif; ?>
    <?php foreach ($family as $fm): ?>
    <div style="padding:8px 0;border-bottom:1px solid var(--line)"><strong><?= e($fm['name']) ?></strong> <small style="color:var(--muted)"><?= e($fm['relation']) ?><?= $fm['age'] ? ' &bull; Age ' . (int)$fm['age'] : '' ?><?= $fm['phone'] ? ' &bull; ' . e($fm['phone']) : '' ?></small></div>
    <?php endforeach; ?>
  </div>
  <div class="card"><h2>Vehicles</h2>
    <?php if (!$vehicles): ?><p style="color:var(--muted)">No vehicles registered.</p><?php endif; ?>
    <?php foreach ($vehicles as $v): ?>
    <div style="padding:8px 0;border-bottom:1px solid var(--line)"><strong><?= e($v['vehicle_no']) ?></strong> <small style="color:var(--muted)"><?= e($v['vehicle_type']) ?><?= $v['model'] ? ' &bull; ' . e($v['model']) : '' ?></small></div>
    <?php endforeach; ?>
    <dl class="kv" style="margin-top:16px">
      <dt>Move-in Date</dt><dd><?= $res['move_in_date'] ? e(date('d M Y', strtotime($res['move_in_date']))) : '-' ?></dd>
      <dt>Joined</dt><dd><?= e(date('d M Y', strtotime($res['created_at']))) ?></dd>
    </dl>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
