<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_login();
if (is_admin()) redirect('bills.php');
$db = db();
$u = current_user();
$bills = [];
$totalDue = 0.0;
if (!empty($u['flat_id'])) {
    $stmt = $db->prepare('SELECT * FROM bills WHERE flat_id = ? ORDER BY bill_month DESC');
    $stmt->execute([$u['flat_id']]);
    $bills = $stmt->fetchAll();
    foreach ($bills as $b) if ($b['status'] !== 'paid') $totalDue += (float)$b['total_amount'] - (float)$b['paid_amount'];
}
$pageTitle = 'My Bills';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>My Maintenance Bills</h1><p><?php if ($totalDue > 0): ?>Total dues: <strong style="color:#B45309"><?= e(inr($totalDue)) ?></strong> — please pay at the society office<?php else: ?>You have no pending dues 🎉<?php endif; ?></p></div>
<?php if ($s = flash('success')): ?><div class="alert alert-success"><?= e($s) ?></div><?php endif; ?>
<?php if ($f = flash('error')): ?><div class="alert alert-error"><?= e($f) ?></div><?php endif; ?>
<div class="card">
  <div class="table-wrap"><table>
    <tr><th>Month</th><th>Total</th><th>Paid</th><th>Due</th><th>Due Date</th><th>Status</th><th>Action</th></tr>
    <?php if (!$bills): ?><tr><td colspan="7" style="text-align:center;color:var(--muted);padding:26px">No bills yet. Bills appear here once the society office generates them.</td></tr><?php endif; ?>
    <?php foreach ($bills as $b): $due = (float)$b['total_amount'] - (float)$b['paid_amount']; ?>
    <tr>
      <td><strong><?= e(date('F Y', strtotime($b['bill_month'] . '-01'))) ?></strong></td>
      <td><?= e(inr((float)$b['total_amount'])) ?></td>
      <td><?= e(inr((float)$b['paid_amount'])) ?></td>
      <td><strong style="color:<?= $due > 0 ? '#B45309' : '#166534' ?>"><?= e(inr($due)) ?></strong></td>
      <td><?= e(date('d M Y', strtotime($b['due_date']))) ?></td>
      <td><span class="pill pill-<?= e($b['status']) ?>"><?= e($b['status']) ?></span></td>
      <td><a class="btn btn-sm btn-view" href="bill-view.php?id=<?= (int)$b['id'] ?>">View<?= $b['status'] === 'paid' ? ' / Receipt' : '' ?></a></td>
    </tr>
    <?php endforeach; ?>
  </table></div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
