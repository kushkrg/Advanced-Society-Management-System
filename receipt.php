<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_login();
$db = db();
$u = current_user();
$pid = (int)($_GET['payment_id'] ?? 0);
$stmt = $db->prepare('SELECT p.*, b.bill_month, b.maintenance_charge, b.water_charge, b.parking_charge, b.other_charge, b.late_fee, b.total_amount, b.paid_amount, f.block_name, f.flat_no, f.flat_type, f.id AS flat_id
    FROM payments p JOIN bills b ON b.id = p.bill_id JOIN flats f ON f.id = b.flat_id WHERE p.id = ?');
$stmt->execute([$pid]);
$p = $stmt->fetch();
if (!$p) { set_flash('error', 'Receipt not found.'); redirect('dashboard.php'); }
if (!is_admin() && (int)($u['flat_id'] ?? 0) !== (int)$p['flat_id']) {
    set_flash('error', 'You can only view receipts for your own flat.');
    redirect('my-bills.php');
}
$resident = flat_resident($db, (int)$p['flat_id']);
$society = setting($db, 'society_name', 'My Society');
$address = setting($db, 'society_address', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Receipt #<?= $pid ?> | <?= e($society) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="receipt">
  <div style="text-align:center;border-bottom:2px solid var(--teal);padding-bottom:16px;margin-bottom:20px">
    <div class="auth-logo" style="margin:0 auto 10px">S</div>
    <h1 style="color:var(--teal-deep)"><?= e($society) ?></h1>
    <?php if ($address): ?><p style="color:var(--muted)"><?= e($address) ?></p><?php endif; ?>
    <p style="font-weight:700;letter-spacing:1px;margin-top:6px">MAINTENANCE PAYMENT RECEIPT</p>
  </div>
  <dl class="kv">
    <dt>Receipt No.</dt><dd>#<?= $pid ?></dd>
    <dt>Payment Date</dt><dd><?= e(date('d M Y', strtotime($p['payment_date']))) ?></dd>
    <dt>Received From</dt><dd><?= $resident ? e($resident['name']) : '—' ?> (Flat <?= e($p['block_name'] . '-' . $p['flat_no']) ?>, <?= e($p['flat_type']) ?>)</dd>
    <dt>Bill Month</dt><dd><?= e(date('F Y', strtotime($p['bill_month'] . '-01'))) ?></dd>
    <dt>Amount Received</dt><dd><strong style="font-size:1.15rem"><?= e(inr((float)$p['amount'])) ?></strong></dd>
    <dt>Payment Method</dt><dd><?= e($p['method']) ?><?= $p['txn_ref'] ? ' &bull; Ref: ' . e($p['txn_ref']) : '' ?></dd>
    <dt>Bill Total</dt><dd><?= e(inr((float)$p['total_amount'])) ?> (Maintenance <?= e(inr((float)$p['maintenance_charge'])) ?> + Water <?= e(inr((float)$p['water_charge'])) ?> + Parking <?= e(inr((float)$p['parking_charge'])) ?> + Other <?= e(inr((float)$p['other_charge'])) ?> + Late Fee <?= e(inr((float)$p['late_fee'])) ?>)</dd>
    <dt>Total Paid So Far</dt><dd><?= e(inr((float)$p['paid_amount'])) ?></dd>
    <dt>Balance Due</dt><dd><?= e(inr((float)$p['total_amount'] - (float)$p['paid_amount'])) ?></dd>
  </dl>
  <p style="margin-top:26px;color:var(--muted);font-size:.9rem">Thank you for your payment. This is a computer-generated receipt from <?= e(APP_NAME) ?>.</p>
  <div class="no-print" style="margin-top:22px;display:flex;gap:10px">
    <button class="btn" onclick="window.print()">🖨 Print Receipt</button>
    <a class="btn btn-light" href="bill-view.php?id=<?= (int)$p['bill_id'] ?>">Back to Bill</a>
  </div>
</div>
</body>
</html>
