<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_admin();
$db = db();

$month = preg_match('/^\d{4}-\d{2}$/', $_GET['month'] ?? '') ? $_GET['month'] : date('Y-m');
$flatId = (int)($_GET['flat_id'] ?? 0);
$statusFilter = in_array($_GET['status'] ?? '', ['paid', 'unpaid', 'partial'], true) ? $_GET['status'] : '';

/* ---- Generate bills for a month ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    verify_csrf('bills.php');
    $genMonth = preg_match('/^\d{4}-\d{2}$/', $_POST['gen_month'] ?? '') ? $_POST['gen_month'] : date('Y-m');
    $rates = settings_all($db);
    $dueDay = max(1, min(28, (int)setting($db, 'due_day', '10')));
    $dueDate = $genMonth . '-' . str_pad((string)$dueDay, 2, '0', STR_PAD_LEFT);
    $flats = $db->query('SELECT * FROM flats ORDER BY block_name, flat_no')->fetchAll();
    $created = 0; $skipped = 0;
    $insert = $db->prepare('INSERT INTO bills (flat_id, bill_month, maintenance_charge, water_charge, parking_charge, other_charge, late_fee, total_amount, paid_amount, due_date, status) VALUES (?, ?, ?, ?, ?, 0, 0, ?, 0, ?, ?)');
    $exists = $db->prepare('SELECT id FROM bills WHERE flat_id = ? AND bill_month = ?');
    foreach ($flats as $flat) {
        $exists->execute([$flat['id'], $genMonth]);
        if ($exists->fetch()) { $skipped++; continue; }
        $amt = compute_bill_amounts($flat['flat_type'], $rates, flat_vehicle_count($db, (int)$flat['id']));
        $insert->execute([$flat['id'], $genMonth, $amt['maintenance'], $amt['water'], $amt['parking'], $amt['total'], $dueDate, 'unpaid']);
        $created++;
    }
    set_flash('success', "Bills generated for " . date('F Y', strtotime($genMonth . '-01')) . ": $created created, $skipped already existed.");
    redirect('bills.php?month=' . $genMonth);
}
/* ---- Delete a bill with no payments ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_bill'])) {
    verify_csrf('bills.php');
    $bid = (int)$_POST['delete_bill'];
    $chk = $db->prepare('SELECT COUNT(*) FROM payments WHERE bill_id = ?');
    $chk->execute([$bid]);
    if ((int)$chk->fetchColumn() > 0) {
        set_flash('error', 'This bill has payments recorded, so it cannot be deleted.');
    } else {
        $db->prepare('DELETE FROM bills WHERE id = ?')->execute([$bid]);
        set_flash('success', 'Bill deleted.');
    }
    redirect('bills.php?month=' . $month);
}

$where = ' WHERE b.bill_month = ?';
$params = [$month];
if ($flatId > 0) { $where .= ' AND b.flat_id = ?'; $params[] = $flatId; }
if ($statusFilter !== '') { $where .= ' AND b.status = ?'; $params[] = $statusFilter; }
$stmt = $db->prepare("SELECT b.*, f.block_name, f.flat_no, f.flat_type,
    (SELECT u.name FROM users u WHERE u.flat_id = b.flat_id AND u.role = 'resident' AND u.is_active = 1 ORDER BY u.id LIMIT 1) AS res_name
    FROM bills b JOIN flats f ON f.id = b.flat_id $where ORDER BY f.block_name, CAST(f.flat_no AS UNSIGNED)");
$stmt->execute($params);
$bills = $stmt->fetchAll();

$sumStmt = $db->prepare('SELECT COALESCE(SUM(total_amount),0) AS billed, COALESCE(SUM(paid_amount),0) AS collected, COUNT(*) AS cnt FROM bills WHERE bill_month = ?');
$sumStmt->execute([$month]);
$sum = $sumStmt->fetch();

$pageTitle = 'Maintenance Bills';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Maintenance Bills — <?= e(date('F Y', strtotime($month . '-01'))) ?></h1><p><?= (int)$sum['cnt'] ?> bill(s) &bull; Billed <?= e(inr((float)$sum['billed'])) ?> &bull; Collected <?= e(inr((float)$sum['collected'])) ?> &bull; Pending <?= e(inr((float)$sum['billed'] - (float)$sum['collected'])) ?></p></div>
<?php if ($s = flash('success')): ?><div class="alert alert-success"><?= e($s) ?></div><?php endif; ?>
<?php if ($f = flash('error')): ?><div class="alert alert-error"><?= e($f) ?></div><?php endif; ?>

<div class="filter-card" style="display:flex;gap:14px;flex-wrap:wrap;justify-content:space-between">
  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap">
    <input type="month" name="month" value="<?= e($month) ?>" style="padding:11px 13px;border:1.5px solid #D8D2C4;border-radius:11px;font:inherit">
    <select name="status" style="width:auto">
      <option value="">All Status</option>
      <option value="paid" <?= $statusFilter === 'paid' ? 'selected' : '' ?>>Paid</option>
      <option value="partial" <?= $statusFilter === 'partial' ? 'selected' : '' ?>>Partial</option>
      <option value="unpaid" <?= $statusFilter === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
    </select>
    <?php if ($flatId): ?><input type="hidden" name="flat_id" value="<?= $flatId ?>"><?php endif; ?>
    <button class="btn" type="submit">View</button>
    <a class="btn btn-light" href="export-dues.php">Dues List CSV</a>
  </form>
  <form method="post" data-confirm="Generate bills for the selected month for all flats that don't have one yet?">
    <?= csrf_field() ?>
    <input type="month" name="gen_month" value="<?= e($month) ?>" style="padding:11px 13px;border:1.5px solid #D8D2C4;border-radius:11px;font:inherit">
    <button class="btn btn-amber" type="submit" name="generate" value="1">⚡ Generate Bills</button>
  </form>
</div>

<div class="card">
  <div class="table-wrap"><table>
    <tr><th>Flat</th><th>Resident</th><th>Maintenance</th><th>Water</th><th>Parking</th><th>Other + Late Fee</th><th>Total</th><th>Paid</th><th>Due</th><th>Status</th><th>Actions</th></tr>
    <?php if (!$bills): ?><tr><td colspan="11" style="text-align:center;color:var(--muted);padding:26px">No bills for this month yet — use <strong>Generate Bills</strong> above.</td></tr><?php endif; ?>
    <?php foreach ($bills as $b): $due = (float)$b['total_amount'] - (float)$b['paid_amount']; ?>
    <tr>
      <td><strong><?= e($b['block_name'] . '-' . $b['flat_no']) ?></strong><br><small style="color:var(--muted)"><?= e($b['flat_type']) ?></small></td>
      <td><?= e($b['res_name'] ?: '—') ?></td>
      <td><?= e(inr((float)$b['maintenance_charge'])) ?></td>
      <td><?= e(inr((float)$b['water_charge'])) ?></td>
      <td><?= e(inr((float)$b['parking_charge'])) ?></td>
      <td><?= e(inr((float)$b['other_charge'] + (float)$b['late_fee'])) ?></td>
      <td><strong><?= e(inr((float)$b['total_amount'])) ?></strong></td>
      <td><?= e(inr((float)$b['paid_amount'])) ?></td>
      <td><strong style="color:<?= $due > 0 ? '#B45309' : '#166534' ?>"><?= e(inr($due)) ?></strong></td>
      <td><span class="pill pill-<?= e($b['status']) ?>"><?= e($b['status']) ?></span></td>
      <td><div class="actions">
        <a class="btn btn-sm btn-view" href="bill-view.php?id=<?= (int)$b['id'] ?>">View / Pay</a>
        <form method="post" data-confirm="Delete this bill? Only possible if no payment is recorded.">
          <?= csrf_field() ?><input type="hidden" name="delete_bill" value="<?= (int)$b['id'] ?>">
          <button class="btn btn-sm btn-danger" type="submit">Delete</button>
        </form>
      </div></td>
    </tr>
    <?php endforeach; ?>
  </table></div>
  <p style="margin-top:12px;color:var(--muted);font-size:.9rem">Rates come from <a href="settings.php">Settings</a> (per flat type + water + parking per vehicle). Adjust an individual bill from its View page.</p>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
