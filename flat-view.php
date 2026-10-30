<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_admin();
$db = db();
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM flats WHERE id = ?');
$stmt->execute([$id]);
$flat = $stmt->fetch();
if (!$flat) { set_flash('error', 'Flat not found.'); redirect('flats.php'); }

$residents = $db->prepare("SELECT * FROM users WHERE flat_id = ? AND role = 'resident' ORDER BY id");
$residents->execute([$id]);
$residents = $residents->fetchAll();
$vehicles = $db->prepare('SELECT v.*, u.name AS owner FROM vehicles v JOIN users u ON u.id = v.user_id WHERE u.flat_id = ?');
$vehicles->execute([$id]);
$vehicles = $vehicles->fetchAll();
$bills = $db->prepare('SELECT * FROM bills WHERE flat_id = ? ORDER BY bill_month DESC LIMIT 12');
$bills->execute([$id]);
$bills = $bills->fetchAll();
$dueStmt = $db->prepare("SELECT COALESCE(SUM(total_amount - paid_amount),0) FROM bills WHERE flat_id = ? AND status IN ('unpaid','partial')");
$dueStmt->execute([$id]);
$dues = (float)$dueStmt->fetchColumn();

$pageTitle = 'Flat ' . flat_label($flat);
require_once __DIR__ . '/includes/header.php';
?>
<?php if ($s = flash('success')): ?><div class="alert alert-success"><?= e($s) ?></div><?php endif; ?>
<div class="detail-hero">
  <div class="detail-avatar" style="border-radius:16px">&#127968;</div>
  <div>
    <h1>Flat <?= e(flat_label($flat)) ?></h1>
    <div class="meta"><?= e($flat['flat_type']) ?> &bull; Floor <?= (int)$flat['floor_no'] ?><?= $flat['area_sqft'] ? ' &bull; ' . (int)$flat['area_sqft'] . ' sq.ft.' : '' ?></div>
    <div class="badges">
      <span class="badge <?= $residents ? 'badge-active' : 'badge-inactive' ?>"><?= $residents ? 'Occupied' : 'Vacant' ?></span>
      <?php if ($dues > 0): ?><span class="badge badge-inactive">Dues: <?= e(inr($dues)) ?></span><?php endif; ?>
    </div>
  </div>
</div>
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:22px">
  <a class="btn" href="bills.php?flat_id=<?= $id ?>">Bills for this Flat</a>
  <a class="btn btn-light" href="flat-edit.php?id=<?= $id ?>">Edit Flat</a>
  <a class="btn btn-light" href="flats.php">All Flats</a>
</div>
<div class="detail-grid">
  <div class="card"><h2>Residents</h2>
    <?php if (!$residents): ?><p style="color:var(--muted)">No resident is assigned to this flat. <a href="resident-add.php">Add a resident</a> and choose this flat.</p><?php endif; ?>
    <?php foreach ($residents as $r): ?>
    <div style="display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--line)">
      <span><strong><?= e($r['name']) ?></strong> <small style="color:var(--muted);text-transform:capitalize"><?= e($r['resident_type']) ?></small></span>
      <a href="resident-view.php?id=<?= (int)$r['id'] ?>">View</a>
    </div>
    <?php endforeach; ?>
    <h2 style="margin-top:22px">Vehicles</h2>
    <?php if (!$vehicles): ?><p style="color:var(--muted)">No vehicles registered.</p><?php endif; ?>
    <?php foreach ($vehicles as $v): ?>
    <div style="padding:7px 0;border-bottom:1px solid var(--line)"><strong><?= e($v['vehicle_no']) ?></strong> <small style="color:var(--muted)"><?= e($v['vehicle_type']) ?><?= $v['model'] ? ' &bull; ' . e($v['model']) : '' ?> &bull; <?= e($v['owner']) ?></small></div>
    <?php endforeach; ?>
  </div>
  <div class="card"><h2>Recent Bills</h2>
    <div class="table-wrap"><table>
      <tr><th>Month</th><th>Total</th><th>Paid</th><th>Status</th></tr>
      <?php if (!$bills): ?><tr><td colspan="4" style="color:var(--muted)">No bills yet.</td></tr><?php endif; ?>
      <?php foreach ($bills as $b): ?>
      <tr><td><a href="bill-view.php?id=<?= (int)$b['id'] ?>"><?= e(date('M Y', strtotime($b['bill_month'] . '-01'))) ?></a></td><td><?= e(inr((float)$b['total_amount'])) ?></td><td><?= e(inr((float)$b['paid_amount'])) ?></td><td><span class="pill pill-<?= e($b['status']) ?>"><?= e($b['status']) ?></span></td></tr>
      <?php endforeach; ?>
    </table></div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
