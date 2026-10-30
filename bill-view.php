<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_login();
$db = db();
$u = current_user();
$id = (int)($_GET['id'] ?? $_POST['bill_id'] ?? 0);
$stmt = $db->prepare('SELECT b.*, f.block_name, f.flat_no, f.flat_type FROM bills b JOIN flats f ON f.id = b.flat_id WHERE b.id = ?');
$stmt->execute([$id]);
$bill = $stmt->fetch();
if (!$bill) { set_flash('error', 'Bill not found.'); redirect(is_admin() ? 'bills.php' : 'my-bills.php'); }
if (!is_admin() && (int)($u['flat_id'] ?? 0) !== (int)$bill['flat_id']) {
    set_flash('error', 'You can only view bills for your own flat.');
    redirect('my-bills.php');
}
$resident = flat_resident($db, (int)$bill['flat_id']);

/* ---- Record payment (admin) ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay'])) {
    if (!is_admin()) redirect('bill-view.php?id=' . $id);
    verify_csrf('bill-view.php?id=' . $id);
    $amount = (float)($_POST['amount'] ?? 0);
    $method = (string)($_POST['method'] ?? 'Cash');
    if (!in_array($method, ['Cash', 'UPI', 'Bank Transfer', 'Cheque'], true)) $method = 'Cash';
    $txn = trim($_POST['txn_ref'] ?? '');
    $payDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['payment_date'] ?? '') ? $_POST['payment_date'] : date('Y-m-d');
    if ($amount <= 0) {
        set_flash('error', 'Enter a valid payment amount.');
        redirect('bill-view.php?id=' . $id);
    }
    // Late fee: charged once when paying after the due date
    $lateFeeApplied = (float)$bill['late_fee'];
    $total = (float)$bill['total_amount'];
    if ($payDate > $bill['due_date'] && $lateFeeApplied <= 0 && $bill['status'] !== 'paid') {
        $fee = (float)setting($db, 'late_fee', '100');
        if ($fee > 0) {
            $total += $fee;
            $db->prepare('UPDATE bills SET late_fee = ?, total_amount = ? WHERE id = ?')->execute([$fee, $total, $id]);
            $lateFeeApplied = $fee;
        }
    }
    $newPaid = (float)$bill['paid_amount'] + $amount;
    $db->prepare('INSERT INTO payments (bill_id, amount, method, txn_ref, payment_date, received_by) VALUES (?, ?, ?, ?, ?, ?)')
       ->execute([$id, $amount, $method, $txn ?: null, $payDate, $u['id']]);
    $db->prepare('UPDATE bills SET paid_amount = ?, status = ? WHERE id = ?')
       ->execute([$newPaid, bill_status_for($newPaid, $total), $id]);
    set_flash('success', 'Payment of ' . inr($amount) . ' recorded.' . ($lateFeeApplied > 0 && $payDate > $bill['due_date'] ? ' Late fee of ' . inr($lateFeeApplied) . ' was applied.' : ''));
    redirect('bill-view.php?id=' . $id);
}
/* ---- Adjust other charges (admin) ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust'])) {
    if (!is_admin()) redirect('bill-view.php?id=' . $id);
    verify_csrf('bill-view.php?id=' . $id);
    $other = max(0, (float)($_POST['other_charge'] ?? 0));
    $newTotal = (float)$bill['maintenance_charge'] + (float)$bill['water_charge'] + (float)$bill['parking_charge'] + $other + (float)$bill['late_fee'];
    $db->prepare('UPDATE bills SET other_charge = ?, total_amount = ?, status = ? WHERE id = ?')
       ->execute([$other, $newTotal, bill_status_for((float)$bill['paid_amount'], $newTotal), $id]);
    set_flash('success', 'Bill adjusted.');
    redirect('bill-view.php?id=' . $id);
}

$payments = $db->prepare('SELECT p.*, usr.name AS received_by_name FROM payments p LEFT JOIN users usr ON usr.id = p.received_by WHERE p.bill_id = ? ORDER BY p.id');
$payments->execute([$id]);
$payments = $payments->fetchAll();
$due = (float)$bill['total_amount'] - (float)$bill['paid_amount'];

$pageTitle = 'Bill ' . date('F Y', strtotime($bill['bill_month'] . '-01'));
require_once __DIR__ . '/includes/header.php';
?>
<?php if ($s = flash('success')): ?><div class="alert alert-success"><?= e($s) ?></div><?php endif; ?>
<?php if ($f = flash('error')): ?><div class="alert alert-error"><?= e($f) ?></div><?php endif; ?>
<div class="page-head"><h1>Maintenance Bill — <?= e(date('F Y', strtotime($bill['bill_month'] . '-01'))) ?></h1>
<p>Flat <strong><?= e(flat_label($bill)) ?></strong> (<?= e($bill['flat_type']) ?>) &bull; <?= $resident ? e($resident['name']) : 'No resident assigned' ?> &bull; Due date <?= e(date('d M Y', strtotime($bill['due_date']))) ?></p></div>

<div class="stats">
  <div class="stat-card"><small>Bill Total</small><div class="num" style="font-size:1.6rem"><?= e(inr((float)$bill['total_amount'])) ?></div></div>
  <div class="stat-card"><small>Paid</small><div class="num" style="font-size:1.6rem"><?= e(inr((float)$bill['paid_amount'])) ?></div></div>
  <div class="stat-card"><small>Due</small><div class="num" style="font-size:1.6rem;color:<?= $due > 0 ? '#B45309' : '#166534' ?>"><?= e(inr($due)) ?></div></div>
  <div class="stat-card"><small>Status</small><div class="num" style="font-size:1.6rem;text-transform:capitalize"><?= e($bill['status']) ?></div></div>
</div>

<div class="detail-grid">
  <div class="card"><h2>Charge Breakdown</h2>
    <dl class="kv">
      <dt>Maintenance (<?= e($bill['flat_type']) ?>)</dt><dd><?= e(inr((float)$bill['maintenance_charge'])) ?></dd>
      <dt>Water</dt><dd><?= e(inr((float)$bill['water_charge'])) ?></dd>
      <dt>Parking</dt><dd><?= e(inr((float)$bill['parking_charge'])) ?></dd>
      <dt>Other Charges</dt><dd><?= e(inr((float)$bill['other_charge'])) ?></dd>
      <dt>Late Fee</dt><dd><?= e(inr((float)$bill['late_fee'])) ?></dd>
      <dt>Total</dt><dd><strong><?= e(inr((float)$bill['total_amount'])) ?></strong></dd>
    </dl>
    <?php if (is_admin()): ?>
    <form method="post" style="margin-top:18px;display:flex;gap:10px;flex-wrap:wrap;align-items:end">
      <?= csrf_field() ?><input type="hidden" name="bill_id" value="<?= $id ?>">
      <div class="field" style="margin:0"><label>Adjust Other Charges (₹)</label><input type="number" name="other_charge" step="0.01" min="0" value="<?= e((string)$bill['other_charge']) ?>" style="width:170px"></div>
      <button class="btn btn-light" type="submit" name="adjust" value="1">Update Bill</button>
    </form>
    <?php endif; ?>
  </div>
  <div class="card"><h2>Payments</h2>
    <?php if (!$payments): ?><p style="color:var(--muted)">No payments recorded yet.</p><?php endif; ?>
    <?php foreach ($payments as $p): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--line)">
      <span><strong><?= e(inr((float)$p['amount'])) ?></strong> <small style="color:var(--muted)"><?= e($p['method']) ?><?= $p['txn_ref'] ? ' &bull; ' . e($p['txn_ref']) : '' ?><br><?= e(date('d M Y', strtotime($p['payment_date']))) ?><?= $p['received_by_name'] ? ' &bull; by ' . e($p['received_by_name']) : '' ?></small></span>
      <a class="btn btn-sm btn-light" href="receipt.php?payment_id=<?= (int)$p['id'] ?>" target="_blank">Receipt</a>
    </div>
    <?php endforeach; ?>
    <?php if (is_admin() && $due > 0): ?>
    <form method="post" style="margin-top:18px">
      <?= csrf_field() ?><input type="hidden" name="bill_id" value="<?= $id ?>">
      <div class="form-grid">
        <div class="field"><label>Amount (₹)</label><input type="number" name="amount" step="0.01" min="1" value="<?= e(rtrim(rtrim(number_format($due, 2, '.', ''), '0'), '.')) ?>" required></div>
        <div class="field"><label>Method</label><select name="method"><option>Cash</option><option>UPI</option><option>Bank Transfer</option><option>Cheque</option></select></div>
        <div class="field"><label>Payment Date</label><input type="date" name="payment_date" value="<?= e(date('Y-m-d')) ?>" required></div>
        <div class="field"><label>Txn Ref / Cheque No. (optional)</label><input type="text" name="txn_ref"></div>
      </div>
      <button class="btn" type="submit" name="pay" value="1">Record Payment</button>
      <p style="color:var(--muted);font-size:.88rem;margin-top:8px">Paying after the due date adds the late fee (<?= e(inr((float)setting($db, 'late_fee', '100'))) ?>) once, automatically.</p>
    </form>
    <?php elseif (!is_admin() && $due > 0): ?>
    <p style="color:var(--muted);margin-top:12px">Please pay <?= e(inr($due)) ?> at the society office (Cash / UPI / Bank Transfer). Your payment and receipt will appear here.</p>
    <?php endif; ?>
    <div style="margin-top:16px"><a class="btn btn-light" href="<?= is_admin() ? 'bills.php?month=' . e($bill['bill_month']) : 'my-bills.php' ?>">Back to Bills</a></div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
