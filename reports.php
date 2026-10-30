<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';
require_admin();
$db = db();
$month = preg_match('/^\d{4}-\d{2}$/', $_GET['month'] ?? '') ? $_GET['month'] : date('Y-m');

$stmt = $db->prepare('SELECT COALESCE(SUM(total_amount),0) AS billed, COALESCE(SUM(paid_amount),0) AS collected, COUNT(*) AS bills,
    SUM(status = \'paid\') AS paid_cnt, SUM(status = \'unpaid\') AS unpaid_cnt, SUM(status = \'partial\') AS partial_cnt FROM bills WHERE bill_month = ?');
$stmt->execute([$month]);
$col = $stmt->fetch();
$pct = (float)$col['billed'] > 0 ? (int)round((float)$col['collected'] / (float)$col['billed'] * 100) : 0;

$expStmt = $db->prepare("SELECT category, SUM(amount) AS total FROM expenses WHERE DATE_FORMAT(expense_date, '%Y-%m') = ? GROUP BY category ORDER BY total DESC");
$expStmt->execute([$month]);
$expByCat = $expStmt->fetchAll();
$expTotal = array_sum(array_map(fn($x) => (float)$x['total'], $expByCat));

$topDues = $db->query("SELECT f.block_name, f.flat_no, f.flat_type, SUM(b.total_amount - b.paid_amount) AS due,
    (SELECT u.name FROM users u WHERE u.flat_id = b.flat_id AND u.role = 'resident' AND u.is_active = 1 ORDER BY u.id LIMIT 1) AS res_name
    FROM bills b JOIN flats f ON f.id = b.flat_id WHERE b.status IN ('unpaid','partial')
    GROUP BY b.flat_id ORDER BY due DESC LIMIT 10")->fetchAll();

$totalFlats = (int)$db->query('SELECT COUNT(*) FROM flats')->fetchColumn();
$occupied = (int)$db->query("SELECT COUNT(DISTINCT flat_id) FROM users WHERE role = 'resident' AND is_active = 1 AND flat_id IS NOT NULL")->fetchColumn();
$totalResidents = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'resident' AND is_active = 1")->fetchColumn();
$totalVehicles = (int)$db->query('SELECT COUNT(*) FROM vehicles')->fetchColumn();
$totalFamily = (int)$db->query('SELECT COUNT(*) FROM family_members')->fetchColumn();

$pageTitle = 'Reports';
require_once __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h1>Reports — <?= e(date('F Y', strtotime($month . '-01'))) ?></h1><p>Collection, expenses and society summary</p></div>
<?php if ($s = flash('success')): ?><div class="alert alert-success"><?= e($s) ?></div><?php endif; ?>
<div class="filter-card">
  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap">
    <input type="month" name="month" value="<?= e($month) ?>" style="padding:11px 13px;border:1.5px solid #D8D2C4;border-radius:11px;font:inherit">
    <button class="btn" type="submit">View Month</button>
    <a class="btn btn-amber" href="export-dues.php">Download Full Dues List (CSV)</a>
  </form>
</div>
<div class="stats">
  <div class="stat-card"><small>Billed</small><div class="num" style="font-size:1.6rem"><?= e(inr((float)$col['billed'])) ?></div><small><?= (int)$col['bills'] ?> bills</small></div>
  <div class="stat-card"><small>Collected</small><div class="num" style="font-size:1.6rem"><?= e(inr((float)$col['collected'])) ?></div><small><?= $pct ?>% of billed</small></div>
  <div class="stat-card"><small>Pending Dues (month)</small><div class="num" style="font-size:1.6rem"><?= e(inr((float)$col['billed'] - (float)$col['collected'])) ?></div><small><?= (int)$col['unpaid_cnt'] ?> unpaid &bull; <?= (int)$col['partial_cnt'] ?> partial &bull; <?= (int)$col['paid_cnt'] ?> paid</small></div>
  <div class="stat-card"><small>Expenses (month)</small><div class="num" style="font-size:1.6rem"><?= e(inr($expTotal)) ?></div></div>
</div>
<div class="progress" style="margin-bottom:22px"><div style="width:<?= $pct ?>%"></div></div>
<div class="detail-grid">
  <div class="card"><h2>Top Dues (All Months)</h2>
    <?php if (!$topDues): ?><p style="color:var(--muted)">No pending dues. Excellent! 🎉</p><?php endif; ?>
    <div class="table-wrap"><table>
      <tr><th>Flat</th><th>Resident</th><th>Due</th></tr>
      <?php foreach ($topDues as $d): ?>
      <tr><td><strong><?= e($d['block_name'] . '-' . $d['flat_no']) ?></strong> <small style="color:var(--muted)"><?= e($d['flat_type']) ?></small></td><td><?= e($d['res_name'] ?: '—') ?></td><td><strong style="color:#B45309"><?= e(inr((float)$d['due'])) ?></strong></td></tr>
      <?php endforeach; ?>
    </table></div>
  </div>
  <div class="card"><h2>Expenses by Category — <?= e(date('M Y', strtotime($month . '-01'))) ?></h2>
    <?php if (!$expByCat): ?><p style="color:var(--muted)">No expenses recorded this month.</p><?php endif; ?>
    <?php foreach ($expByCat as $c): ?>
    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line)"><span><?= e($c['category']) ?></span><strong><?= e(inr((float)$c['total'])) ?></strong></div>
    <?php endforeach; ?>
    <h2 style="margin-top:22px">Society Summary</h2>
    <dl class="kv">
      <dt>Flats</dt><dd><?= $totalFlats ?> (<?= $occupied ?> occupied, <?= $totalFlats - $occupied ?> vacant)</dd>
      <dt>Resident Accounts</dt><dd><?= $totalResidents ?></dd>
      <dt>Family Members</dt><dd><?= $totalFamily ?></dd>
      <dt>Vehicles</dt><dd><?= $totalVehicles ?></dd>
    </dl>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
